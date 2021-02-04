<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\DetailTransaction;
use DB;
use App\Payment;
use App\Http\Resources\TransactionCollection;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'customer_id' => 'required',
            'detail' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $user = $request->user();
            $transaction = Transaction::create([
                'customer_id' => $request->customer_id['id'],
                'user_id' => $user->id,
                'amount' => 0
            ]);

            $amount = 0; //TAMBAHKAN BAGIAN INI UNTUK DI CALCULATE SEBAGAI AMOUNT TOTAL TRANSAKSI
            foreach ($request->detail as $row) {
                if (!is_null($row['laundry_price'])) {
                    $subtotal = $row['laundry_price']['price'] * $row['qty'];
                    if ($row['laundry_price']['unit_type'] == 'Kilogram') {
                        $subtotal = ($row['laundry_price']['price'] * $row['qty']) / 1000; //MODIFIKASI BAGIAN INI KARENA HARUSNYA /1000 SEBAB SATUANNYA KILOGRAM
                    }

                    $start_date = Carbon::now(); //DEFINISIKAN UNTUK START DATE-NYA
                    $end_date = Carbon::now()->addHours($row['laundry_price']['service']); //DEFAULTNYA KITA DEFINISIKAN END DATE MENGGUNAKAN ADDHOURS
                    if ($row['laundry_price']['service_type'] == 'Hari') {
                        //AKAN TETAPI, JIKA SERVICENYA ADALAH HARI MAKA END_DATE AKAN DI-REPLACE DENGAN ADDDAYS()
                        $end_date = Carbon::now()->addDays($row['laundry_price']['service']);
                    }

                    DetailTransaction::create([
                        'transaction_id' => $transaction->id,
                        'laundry_price_id' => $row['laundry_price']['id'],
                        'laundry_type_id' => $row['laundry_price']['laundry_type_id'],
                    
                        //SIMPAN INFORMASINYA KE DATABASE
                        'start_date' => $start_date->format('Y-m-d H:i:s'),
                        'end_date' => $end_date->format('Y-m-d H:i:s'),
                    
                        'qty' => $row['qty'],
                        'price' => $row['laundry_price']['price'],
                        'subtotal' => $subtotal
                    ]);

                    $amount += $subtotal; //KALKULASIKAN AMOUNT UNTUK SETIAP LOOPINGNYA
                }
            }
            $transaction->update(['amount' => $amount]); //UPDATE INFORMASI PADA TABLE TRANSACTIONS
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $transaction]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        //LAKUKAN QUERY KE DATABASE UNTUK MENCARI ID TRANSAKSI TERKAIT
        //KITA JUGA ME-LOAD DATA LAINNYA MENGGUNAKAN EAGER LOADING, MAKA SELANJUTNYA AKAN DI DEFINISIKAN FUNGSINYA
        $transaction = Transaction::with(['customer', 'payment', 'detail', 'detail.product'])->find($id);
        return response()->json(['status' => 'success', 'data' => $transaction]);
    }

    public function completeItem(Request $request)
    {
        //VALIDASI UNTUK MENGECEK IDNYA ADA ATAU TIDAK
        $this->validate($request, [
            'id' => 'required|exists:detail_transactions,id'
        ]);

        //LOAD DATA DETAIL TRANSAKSI BERDASARKAN ID
        $transaction = DetailTransaction::with(['transaction.customer'])->find($request->id);
        //UPDATE STATUS DETAIL TRANSAKSI MENJADI 1 YANG BERARTI SELESAI
        $transaction->update(['status' => 1]);
        //UPDATE DATA CUSTOMER TERKAIT DENGAN MENAMBAHKAN 1 POINT
        $transaction->transaction->customer()->update(['point' => $transaction->transaction->customer->point + 1]);
        return response()->json(['status' => 'success']);
    }

    public function makePayment(Request $request)
    {
        $this->validate($request, [
            'transaction_id' => 'required|exists:transactions,id',
            'amount' => 'required|integer'
        ]);

        DB::beginTransaction();
        try {
            $transaction = Transaction::with(['customer'])->find($request->transaction_id);

            $customer_change = 0;
            //LAKUKAN PENGECEKAN, JIKA VIA_DEPOSIT TRUE
            if ($request->via_deposit) {
                //MAKA DI CEK LAGI, JIKA DEPOSIT CUSTOMER KURANG DARI TOTAL TAGIHAN
                if ($transaction->customer->deposit < $request->amount) {
                    //MAKA KIRIM RESPONSE KALO ERROR PEMBAYARANNYA
                    return response()->json(['status' => 'error', 'data' => 'Deposit Kurang!']);
                }
            
                //SELAIN ITU, MAKA PERBAHARUI DEPOSIT CUSTOMER
                $transaction->customer()->update(['deposit' => $transaction->customer->deposit - $request->amount]);
            
            //JIKA VALUE VIA_DEPOSIT FALSE (VIA CASH)
            } else {
                //MAKA DI CEK LAGI, JIKA ADA KEMBALIANNYA
                if ($request->customer_change) {
                    //DAPATKAN KEMBALIAN
                    $customer_change = $request->amount - $transaction->amount;
                    
                    //DAN TAMBAHKAN KE DEPOSIT CUSTOMER
                    $transaction->customer()->update(['deposit' => $transaction->customer->deposit + $customer_change]);
                }
            }

            Payment::create([
                'transaction_id' => $transaction->id,
                'amount' => $request->amount,
                'customer_change' => $customer_change,
                'type' => $request->via_deposit //UBAH BAGIAN TIPE PEMBAYARANNYA, 0 = CASH, 1 = DEPOSIT
            ]);
            $transaction->update(['status' => 1]);
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'failed', 'data' => $e->getMessage()]);
        }
    }

    public function index()
    {
        $search = request()->q; //TAMPUNG QUERY PENCARIAN DARI URL
        $user = request()->user(); //GET USER YANG SEDANG LOGIN
    
        //BUAT QUERY KE DATABASE DENGAN ME-LOAD RELASI TABLE TERKAIT DAN DIURUTKAN BERDASARKAN CREATED_AT
        //whereHas() DIGUNAKAN UNTUK MEN-FILTER NAMA CUSTOMER YANG DICARI USER, AKAN TETAPI NAMA TERSEBUT BERADA PADA TABLE CUSTOMERS
        //PARAMETER PERTAMA DARI whereHas() ADALAH NAMA RELASI YANG DIDEFINISIKAN DIMODEL
        $transaction = Transaction::with(['user', 'detail', 'customer'])->orderBy('created_at', 'DESC')
            ->whereHas('customer', function($q) use($search) {
                $q->where('name', 'LIKE', '%' . $search . '%');
            });

    //JIKA FILTERNYA ADALAH 0 DAN 1. DIMANA 0 = PROSES, 1 = SELESAI DAN 2 = SEMUA DATA
        if (in_array(request()->status, [0,1])) {
            //MAKA AMBIL DATA BERDASARKAN STATUS TERSEBUT
            $transaction = $transaction->where('status', request()->status);
        }
    
        //JIKA ROLENYA BUKAN SUPERADMIN
        if ($user->role != 0) {
            //MAKA USER HANYA AKAN MENDAPATKAN TRANSAKSI MILIKNYA SAJA
            $transaction = $transaction->where('user_id', $user->id);
        }
        $transaction = $transaction->paginate(10);
        return new TransactionCollection($transaction);
    }
}

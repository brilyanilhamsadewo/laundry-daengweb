<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Transaction;
use App\DetailTransaction;
use DB;

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
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
}

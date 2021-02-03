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
        //VALIDASI
        $this->validate($request, [
            'customer_id' => 'required',
            'detail' => 'required'
        ]);

        //MENGGUNAKAN DATABASE TRANSACTION
        DB::beginTransaction();
        try {
            $user = $request->user(); //GET USER YANG SEDANG LOGIN
            //BUAT DATA TRANSAKSI
            $transaction = Transaction::create([
                'customer_id' => $request->customer_id['id'],
                'user_id' => $user->id,
                'amount' => 0
            ]);
            
            //KARENA DATA ITEM NYA LEBIH DARI SATU MAKA KITA LOOPING
            foreach ($request->detail as $row) {
                //DIMANA DATA YANG DITERIMA HANYALAH ITEM YANG LAUNDRY_PRICE (PRODUCT) NYA SUDAH DIPILIH
                if (!is_null($row['laundry_price'])) {
                    //MELAKUKAN PERHITUNGAN KEMBALI DARI SISI BACKEND UNTUK MENENTUKAN SUBTOTAL
                    $subtotal = $row['laundry_price']['price'] * $row['qty'];
                    if ($row['laundry_price']['unit_type'] == 'Kilogram') {
                        $subtotal = $row['laundry_price']['price'] * ($row['qty'] / 100);
                    }

                    //MENYIMPAN DATA DETAIL TRANSAKSI
                    DetailTransaction::create([
                        'transaction_id' => $transaction->id,
                        'laundry_price_id' => $row['laundry_price']['id'],
                        'laundry_type_id' => $row['laundry_price']['laundry_type_id'],
                        'qty' => $row['qty'],
                        'price' => $row['laundry_price']['price'],
                        'subtotal' => $subtotal
                    ]);
                }
            }
            //APABILA TIDAK TERJADI ERROR, MAKA KITA COMMIT AGAR BENAR2 MENYIMPAN DATANYA
            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollback(); //JIKA TERJADI ERROR, MAKA DIROLLBACK AGAR DATA YANG BERHASIL DISIMPAN DIHAPUS
            return response()->json(['status' => 'error', 'data' => $e->getMessage()]);
        }
    }
}

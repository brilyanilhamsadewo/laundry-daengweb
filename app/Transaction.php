<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = []; //TAMBAHKAN LINE INI

    public function detail()
    {
        //TRANSAKSI KE DETAIL MENGGUNAKAN RELASI ONE TO MANY
        return $this->hasMany(DetailTransaction::class);
    }

    public function customer()
    {
        //TRANSAKSI KE CUSTOMER MELAKUKAN REFLEK DATA TERKAIT MENGGUAKAN BELONGSTO
        return $this->belongsTo(Customer::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}

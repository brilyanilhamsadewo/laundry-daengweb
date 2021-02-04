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

    protected $appends = ['status_label']; //APPEND ACCESSORNYA AGAR DITAMPILKAN DIJSON YANG DIRETURN

    //INI ADALAH ACCESSOR UNTUK CUSTOM FIELD STATUS YANG AKAN DIAPPEND KE JSON
    public function getStatusLabelAttribute()
    {
        //JIKA STATUS NYA 1 
        if ($this->status == 1) {
            //MAKA VALUENYA ADALAH HTML YANG BERISI LABEL SUCCESS
            return '<span class="label label-success">Selesai</span>';
        }
        //SELAIN ITU MENAMPILKAN LABEL PRIMARY
        return '<span class="label label-primary">Proses</span>';
    }

    //BUAT RELASI ANTARA USER DAN TRANSACTION
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

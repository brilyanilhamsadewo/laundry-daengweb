<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetailTransaction extends Model
{
    protected $guarded = [];
    protected $dates = ['start_date', 'end_date'];
    protected $appends = ['service_time', 'status_label']; //AGAR ATTRIBUTE BARU TERSEBUT MUNCUL DI DALAM JSON, MAKA APPEND NAMA ATTRIBUTENYA. Contoh: ServiceTime menjadi service_time. kata GET dan ATTRIBUTE dibuang

    //KITA BUAT ATTRIBUTE BARU UNTUK SERVICE_TIME
    public function getServiceTimeAttribute()
    {
        //ISINYA ADALAH START DATE DAN END DATE DI REFORMAT SESUAI TANGGAL INDONESIA
        return $this->start_date->format('d-m-Y H:i:s') . ' s/d ' . $this->end_date->format('d-m-Y H:i:s');
    }

    //BUAT ATTRIBUTE BARU UNTUK LABEL STATUS
    public function getStatusLabelAttribute()
    {
        if ($this->status == 1) {
            return '<span class="label label-success">Selesai</span>';
        }
        return '<span class="label label-default">Proses</span>';
    }

    //RELASI KE TABLE TRANSACTIONS
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    //RELASI KE LAUNDRY_PRICES
    public function product()
    {
        return $this->belongsTo(LaundryPrice::class, 'laundry_price_id');
    }
}

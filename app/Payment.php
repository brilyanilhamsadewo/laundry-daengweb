<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];
    protected $appends = ['type_label']; //APPEND KE JSON OBJECT

    //MEMBUAT ATTRIBUTE BARU
    public function getTypeLabelAttribute()
    {
        if ($this->type == 0) {
            return 'Cash';
        }
        return 'Deposit';
    }
}
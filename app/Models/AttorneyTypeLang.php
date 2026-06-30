<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttorneyTypeLang extends Model
{
    protected $table = 'types_of_attorney_lang';
    protected $primaryKey = 'id'; // если есть

    public function types_of_attorney()
    {
        return $this->belongsTo(AttorneyType::class, 'attorney_type_id', 'attorney_type_id');
    }
}

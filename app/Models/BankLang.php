<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankLang extends Model
{
    use HasFactory;
    protected $table = 'banks_lang';
    protected $primaryKey = 'id'; // если есть

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'bank_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    protected $table = 'banks';
    protected $primaryKey = 'bank_id';

    public function banks_lang()
    {
        return $this->hasMany(BankLang::class, 'bank_id', 'bank_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;

    protected $table = 'agreements';
    protected $primaryKey = 'agreement_id';

    protected $casts = [
        'data' => 'array',
    ];
}

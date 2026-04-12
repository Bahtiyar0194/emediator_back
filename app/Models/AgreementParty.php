<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementParty extends Model
{
    use HasFactory;
    protected $table = 'agreement_parties';
    protected $primaryKey = 'id';
}
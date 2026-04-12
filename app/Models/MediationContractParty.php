<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediationContractParty extends Model
{
    use HasFactory;

    protected $table = 'mediation_contract_parties';
    protected $primaryKey = 'id';
}

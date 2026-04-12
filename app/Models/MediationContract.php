<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediationContract extends Model
{
    use HasFactory;

    protected $table = 'mediation_contracts';
    protected $primaryKey = 'mediation_contract_id';
}

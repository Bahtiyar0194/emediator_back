<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementTypicalPoint extends Model
{
    use HasFactory;
    protected $table = 'agreement_typical_points';
    protected $primaryKey = 'point_id';
}
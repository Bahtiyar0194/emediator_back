<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementType extends Model
{
    use HasFactory;
    protected $table = 'types_of_agreements';
    protected $primaryKey = 'agreement_type_id';

}

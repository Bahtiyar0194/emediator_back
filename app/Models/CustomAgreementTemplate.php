<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomAgreementTemplate extends Model
{
    use HasFactory;
    protected $table = 'custom_agreement_templates';
    protected $primaryKey = 'template_id';
}

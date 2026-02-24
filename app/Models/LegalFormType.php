<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalFormType extends Model
{
    use HasFactory;
    protected $table = 'types_of_legal_forms';
    protected $primaryKey = 'legal_form_id';

    public function types_of_legal_forms_lang()
    {
        return $this->hasMany(LegalFormTypeLang::class, 'legal_form_id', 'legal_form_id');
    }
}
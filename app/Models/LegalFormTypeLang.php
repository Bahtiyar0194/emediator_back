<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalFormTypeLang extends Model
{
    protected $table = 'types_of_legal_forms_lang';
    protected $primaryKey = 'id'; // если есть

    public function types_of_legal_forms()
    {
        return $this->belongsTo(LegalFormType::class, 'legal_form_id', 'legal_form_id');
    }
}

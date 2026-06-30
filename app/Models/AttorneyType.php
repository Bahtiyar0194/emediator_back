<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttorneyType extends Model
{
    use HasFactory;
    protected $table = 'types_of_attorney';
    protected $primaryKey = 'attorney_type_id';

    public function types_of_attroney_lang()
    {
        return $this->hasMany(LegalFormTypeLang::class, 'attorney_type_id', 'attorney_type_id');
    }
}

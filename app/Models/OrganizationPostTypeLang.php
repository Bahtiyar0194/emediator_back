<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationPostTypeLang extends Model
{
    use HasFactory;
    protected $table = 'types_of_organization_posts_lang';
    protected $primaryKey = 'id'; // если есть

    public function types_of_organization_posts()
    {
        return $this->belongsTo(OrganizationPostType::class, 'post_type_id', 'post_type_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationPostType extends Model
{
    use HasFactory;
    protected $table = 'types_of_organization_posts';
    protected $primaryKey = 'post_type_id';

    public function types_of_organization_posts_lang()
    {
        return $this->hasMany(OrganizationPostTypeLang::class, 'post_type_id', 'post_type_id');
    }
}

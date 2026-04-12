<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mediator extends Model
{
    use HasFactory;
    protected $table = 'mediators';
    protected $primaryKey = 'id';
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempSignToken extends Model
{
    use HasFactory;
    protected $table = 'temp_sign_tokens';
    protected $primaryKey = 'token_id';
}

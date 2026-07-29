<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempAuthToken extends Model
{
    use HasFactory;
    protected $table = 'temp_auth_tokens';
    protected $primaryKey = 'token_id';
}

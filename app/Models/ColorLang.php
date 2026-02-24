<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ColorLang extends Model
{
    use HasFactory;
    protected $table = 'colors_lang';
    protected $primaryKey = 'id'; // если есть

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id', 'color_id');
    }
}

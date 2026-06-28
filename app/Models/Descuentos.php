<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Descuentos extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable =
    [
        'producto',
        'precio',
        'inicio',
        'fin',
        'descuento',
        'valor',
        'sincro_id'
    ];
    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }
}

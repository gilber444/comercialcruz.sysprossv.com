<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Aperturas extends Model
{
    use HasFactory;

    protected $fillable = [
        'caja',
        'sucursal',
        'empresa',
        'fechaApertura',
        'horaApertura',
        'inicio',
        'final',
        'FcierreApertura',
        'HcierreApertura',
        'estado',
        'cajero',
        'sincro_id', // opcionalmente lo agregas al fillable
        
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ControlNumeracion extends Model
{
    use HasFactory;

    protected $table = 'control_numeraciones';

    protected $fillable = [
        'empresa',
        'sucursal',
        'tipoDte',
        'ambiente',
        'anio',
        'correlativo',
        'sincro_id'

    ];
    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }

            // Ambiente por defecto (1 = pruebas)
            if (is_null($model->ambiente)) {
                $model->ambiente = 1;
            }

            // Año actual
            if (is_null($model->anio)) {
                $model->anio = now()->year;
            }
        });
    }
}

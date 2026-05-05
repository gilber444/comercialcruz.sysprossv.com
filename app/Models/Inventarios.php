<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Inventarios extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'producto',
        //'empresa',
        'sucursal',
        'existencia',
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

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rempresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }
}

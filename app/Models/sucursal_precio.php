<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class sucursal_precio extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'precio',
        'sucursal',
        'activo',
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

    public function Rprecio()
    {
        return $this->belongsTo(Precios::class, 'precio');
    }

    public function Rsucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

}

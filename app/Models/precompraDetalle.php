<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class precompraDetalle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'precompra',
        'producto',
        'medida',
        'tipoItem',
        'codigo',
        'descripcion',
        'cantidad',
        'uniMedida',
        'precioUni',
        'montoDescu',
        'ventaNoSuj',
        'ventaExenta',
        'ventaGravada',
        'status',
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

    public function precompra()
    {
        return $this->belongsTo(Precompra::class, 'precompra');
    }

    public function productos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function medidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class ComprasDetalles extends Model
{
    use HasFactory;

    protected $fillable =
    [
        'compra',
        'producto',
        'medida',
        'cantidad',
        'ingreso',
        'costo',
        'newcosto',
        'total',
        'fechaVencimiento',
        'ingreso',
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

    public function RProductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Compras()
    {
        return $this->belongsTo(Compras::class, 'compra');
    }
}

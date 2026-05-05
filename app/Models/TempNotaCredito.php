<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempNotaCredito extends Model
{
    use HasFactory;
    protected $fillable = [
        'venta',
        'producto',
        'medida',
        'name',
        'unidad',
        'descargar',
        'cantidad',
        'precio',
        'descuento',
        'subtotal',
        'iva',
        'total',
        'costo',
        'costo_total',
        'utilidad_uni',
        'utilidad',
        'usuario'
    ];

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rventas()
    {
        return $this->belongsTo(Ventas::class, 'venta');
    }

    public function Rmedidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }
}

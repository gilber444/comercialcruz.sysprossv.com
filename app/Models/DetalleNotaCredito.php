<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleNotaCredito extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'usuario',
        'nota_credito'
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

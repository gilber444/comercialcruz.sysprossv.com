<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempEventoRetorno extends Model
{
    use HasFactory;

    protected $table = 'temp_evento_retornos';

    protected $fillable = [
        'usuario',
        'venta',
        'ventaDetalle',
        'producto',
        'name',
        'unidad',
        'uniMedida',
        'cantidad',
        'vendida',
        'descargar',
        'precio',
        'descuento',
        'iva',
        'total',
    ];

    public function Rproducto()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function RventaDetalle()
    {
        return $this->belongsTo(VentasDetalles::class, 'ventaDetalle');
    }
}

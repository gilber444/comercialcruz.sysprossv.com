<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoRetornoDetalle extends Model
{
    use HasFactory;

    protected $table = 'evento_retorno_detalles';

    protected $fillable = [
        'evento',
        'numItem',
        'tipoItem',
        'producto',
        'ventaDetalle',
        'codigoGeneracion',
        'codigo',
        'descripcion',
        'cantidad',
        'descargar',
        'uniMedida',
        'precioUni',
        'montoDescu',
        'ventaNoSuj',
        'ventaExenta',
        'ventaGravada',
        'ivaItem',
        'psv',
        'noGravado',
    ];

    public function Revento()
    {
        return $this->belongsTo(EventoRetorno::class, 'evento');
    }

    public function Rproducto()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function RventaDetalle()
    {
        return $this->belongsTo(VentasDetalles::class, 'ventaDetalle');
    }
}

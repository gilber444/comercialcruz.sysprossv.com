<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SujetoExcluidosDetalles extends Model
{ 
    use HasFactory;

    protected $table = 'sujeto_excluidos_detalles';

    protected $fillable = [
        'sujeto_excluido_id',
        'producto',
        'cantidad',      
        'unidad',
        'descripcion',
        'precio_unitario',
        'descuento',
        'ventas',
    ];

    public function RsujetoExcluido()
    {
        return $this->belongsTo(SujetoExcluido::class, 'sujeto_excluido_id');
    }

    public function Rproducto()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SujetoExcluido extends Model
{ 
    use HasFactory, SoftDeletes;

    protected $table = 'sujeto_excluidos';

    protected $fillable = [
        'empresa',
        'sucursal',
        'user',
        'codigo_generacion',
        'numero_control',
        'sello_recepcion',
        'modelo_facturacion',
        'tipo_transmision',
        'fecha_hora_generacion',
        'cliente',
        'emisor',
        'total_pagar',
        'valor_letras',
        'observaciones',
        'tipo',
        'estado',
        
    ];

    public function Rempresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rsucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rcliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    public function Rusuario()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function Rreceptor()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }
}
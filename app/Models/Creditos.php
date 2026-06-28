<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Creditos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'creditos';

    protected $fillable = [
        'venta',
        'cliente',
        'empresa',
        'sucursal',
        'correlativo',
        'fechaCredito',
        'fechaPago',
        'total',
        'saldo',
        'estado',
        'sincro_id' // opcionalmente lo agregas al fillable
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

    /**
     * Relación con el modelo Venta
     */
    public function Rventas()
    {
        return $this->belongsTo(Ventas::class, 'venta');
    }

    /**
     * Relación con el modelo Cliente
     */
    public function Rclientes()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    /**
     * Relación con el modelo Empresa
     */
    public function Rempresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    /**
     * Relación con el modelo Sucursal
     */
    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }
}

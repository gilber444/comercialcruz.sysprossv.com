<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CuentasPagar extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'compra',
        'compra_id_vps',
        'proveedor',
        'monto_total',
        'saldo',
        'estado',
        'fecha_vencimiento',
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

    public function Rcompras()
    {
        return $this->belongsTo(Compras::class, 'compra');
    }

    public function Rproveedores()
    {
        return $this->belongsTo(Proveedores::class, 'proveedor');
    }

}

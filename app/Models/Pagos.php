<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Pagos extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'correlativo',
        'fecha',
        'hora',
        'user',
        'concepto',
        'total',
        'cuenta_pagar',
        'tipo_pago',
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

     // Relaciones
     public function Ruser()
     {
         return $this->belongsTo(User::class, 'user');
     }

     public function RcuentaPagar()
     {
         return $this->belongsTo(CuentasPagar::class, 'cuenta_pagar');
     }

     public function RtipoPago()
     {
         return $this->belongsTo(TipoPagos::class, 'tipo_pago');
     }

}

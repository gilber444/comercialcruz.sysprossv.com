<?php

namespace App\Models;

use App\Models\Empresas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ililluminate\Support\Str;

class Parametros extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $hidden = ['token'];

    protected $fillable = [
        'empresa',
        'sucursal',
        'caja',
        'token',
        'full',
        'slip',
        'multiple',
        'centralizada',
        'ticket',
        'tcorrelativo',
        'tresulucion',
        'tserie',
        'consumidor',
        'concorrelativo',
        'conresolucion',
        'conserie',
        'credito',
        'crecorrelativo',
        'creresolucion',
        'creserie',
        'notacredito',
        'nccorrelativo',
        'ncresolucion',
        'ncserie',
        'notadebito',
        'ndcorrelativo',
        'ndresolucion',
        'ndserie',
        'cotizacion',
        'cocorrelativo',
        'tasa',
        'ventamin',
        'dte',
        'dteAutomatico',
        'tiquedte',
        'envio',
        'descuento',
        'estado',
        'filtro',
        'sincro_id',
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

    public function sucursales(){
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function empresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }
}

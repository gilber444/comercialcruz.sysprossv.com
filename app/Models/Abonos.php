<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Abonos extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'empresa',
        'sucursal',
        'credito',
        'correlativo',
        'fecha',
        'hora',
        'monto',
        'user',
        'estado',
        'sincro_id', // opcionalmente lo agregas al fillable

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
    public function Rempresas()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rsucursales()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rcreditos()
    {
        return $this->belongsTo(Creditos::class, 'credito');
    }

    public function Rusers()
    {
        return $this->belongsTo(User::class, 'user');
    }
}

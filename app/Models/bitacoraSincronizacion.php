<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class bitacoraSincronizacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sincro_id',
        'tabla',
        'origen',
        'destino',
        'maquina',
        'registros',
        'insertados',
        'actualizados',
        'estado',
        'mensaje',
        'fecha',
    ];

    protected $casts = [
        'fecha'       => 'datetime',
        'registros'   => 'integer',
        'insertados'  => 'integer',
        'actualizados'=> 'integer',
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

    // Scope para errores
    public function scopeErrores($query)
    {
        return $query->where('estado', 'ERROR');
    }

    // Scope para una tabla específica
    public function scopeDeTabla($query, $tabla)
    {
        return $query->where('tabla', $tabla);
    }

    // Scope por máquina
    public function scopeDeMaquina($query, $maquina)
    {
        return $query->where('maquina', $maquina);
    }
}

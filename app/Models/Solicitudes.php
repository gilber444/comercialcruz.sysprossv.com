<?php

namespace App\Models;

use App\Models\SolicitudesDetalles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Solicitudes extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origen',
        'destino',
        'numero',
        'fecha',
        'detalle',
        'solicitante',
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

     public function SolicitudesDetalles()
    {
        return $this->hasMany(SolicitudesDetalles::class);
    }

    public function Rorigen()
    {
        return $this->belongsTo(Sucursales::class, 'origen');
    }

    public function Rdestino()
    {
        return $this->belongsTo(Sucursales::class, 'destino');
    }

    public function Rsolicitante()
    {
        return $this->belongsTo(User::class, 'solicitante');
    }
}

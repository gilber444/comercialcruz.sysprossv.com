<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Empresas extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa',
        'razon',
        'direccion',
        'telefono',
        'responsable',
        'registro',
        'giro',
        'nit',
        'tipoContribuyente',
        'actividad',
        'desActividad',
        'correo',
        'apiPassword',
        'departamento',
        'municipio',
        'distrito',
        'plan',
        'image',
        'certificado',
        'ambiente',
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

    public function sucursales()
    {
        return $this->hasMany(Sucursales::class);
    }

    public function RmensajeriaTickets()
    {
        return $this->hasOne(MensajeriaTicket::class, 'empresa', 'id');
    }

    public function Rambientes()
    {
        return $this->belongsTo(AmbienteDestino::class, 'ambiente');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Actividades extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user', 'empresa', 'sucursal', 'caja', 'status','sincro_id'
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

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function caja()
    {
        return $this->belongsTo(Parametros::class, 'caja');
    }
}

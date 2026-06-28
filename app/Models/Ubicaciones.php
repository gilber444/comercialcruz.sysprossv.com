<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Ubicaciones extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario',
        'empresa',
        'sucursal',
        'caja',
        'estado',
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


    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}

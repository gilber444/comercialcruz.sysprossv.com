<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Productos extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable =
    [
        'codebar1',
        'codebar2',
        'codebar3',
        'codealternativo',
        'nombreProducto',
        'categoria',
        'familia',
        'medida',
        'proveedor1',
        'proveedor2',
        'proveedor3',
        'activo',
        'exento',
        'caja',
        'fraccionario',
        'medidamh',
        'contenedor',
        'maximo',
        'minimo',
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

    public function Rcategoria()
    {
        return $this->belongsTo(Categorias::class, 'categoria');
    }

    public function Rfamilias()
    {
        return $this->belongsTo(Familias::class, 'familia');
    }

    public function Rmedidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }

    public function RmedidasMH()
    {
        return $this->belongsTo(UnidadMedida::class, 'medidamh');
    }

    public function Rinventario()
    {
        return $this->hasMany(Inventarios::class, 'producto', 'id');
    }

    public function precioBase()
    {
        return $this->hasOne(Precios::class, 'producto')->orderBy('cantidad', 'asc');
    }

}

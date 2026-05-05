<?php

namespace App\Models;

use App\Models\HojaInventario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class HojaInventarioDetalles extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['hoja', 'codebar', 'producto', 'nombre', 'medida', 'cantidadAnterior', 'cantidadActual', 'diferencia', 'costo', 'total','sincro_id'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }
    public function Rhojas()
    {
        return $this->belongsTo(HojaInventario::class, 'hoja');
    }

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rmedidas()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }
}

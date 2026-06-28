<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AjustesDetalles extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable =
    [
        'ajuste',
        'producto',
        'inventario',
        'medida',
        'cantidad',
        'ingreso',
        'costo',
        'total',
        'status',
        'aplicado_local',
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

    public function Rajuste()
    {
        return $this->belongsTo(Ajustes::class, 'ajuste');
    }

    public function Rproductos()
    {
        return $this->belongsTo(Productos::class, 'producto');
    }

    public function Rinventario()
    {
        return $this->belongsTo(Inventarios::class, 'inventario');
    }

    public function Rmedida()
    {
        return $this->belongsTo(Medidas::class, 'medida');
    }
}

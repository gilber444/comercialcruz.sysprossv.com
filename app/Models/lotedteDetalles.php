<?php

namespace App\Models;

use App\Models\dte;
use App\Models\lotedte;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class lotedteDetalles extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'lote',
        'dte',
        'estado',
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

    public function loteDte()
    {
        return $this->belongsTo(lotedte::class, 'lote');
    }

    public function dte()
    {
        return $this->belongsTo(dte::class, 'dte');
    }
}

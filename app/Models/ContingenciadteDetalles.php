<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ContingenciadteDetalles extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['contingencia', 'noItem', 'dte', 'estado', 'sincro_id'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }

    public function contingenciaDte()
    {
        return $this->belongsTo(ContingenciaDte::class, 'contingencia');
    }

    public function dtes()
    {
        return $this->belongsTo(dte::class, 'dte');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Municipios extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['codigo', 'municipio', 'departamento', 'status','sincro_id'];

    // Genera sincro_id si no viene seteado
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->sincro_id)) {
                $model->sincro_id = (string) Str::uuid();
            }
        });
    }

    public function departamentos(){
        return $this->belongsTo(Departamentos::class);
    }

    public function distritos()
    {
        return $this->hasMany(Distritos::class, 'municipio');
    }
}

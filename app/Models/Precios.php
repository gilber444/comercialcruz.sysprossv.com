<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Str;



class Precios extends Model

{

    use HasFactory;

    use SoftDeletes;



    protected $fillable =

    [

        'producto',

        'familia',

        'medida',

        'codebar',

        'cantidad',

        'presentacion',

        'costosiva',

        'costociva',

        'utilidad',

        'pventasiva',

        'pvventa',

        'escala',

        'sincro_id'



    ];

    protected $hidden = ['costosiva', 'costociva', 'utilidad'];



    // Genera sincro_id si no viene seteado

    protected static function booted()

    {

        static::creating(function ($model) {

            if (empty($model->sincro_id)) {

                $model->sincro_id = (string) Str::uuid();

            }

        });

    }



    public function Rproductos()

    {

        return $this->belongsTo(Productos::class, 'producto');

    }



    public function Rfamilia()

    {

        return $this->belongsTo(Familias::class, 'familia');

    }



    public function Rmedida()

    {

        return $this->belongsTo(Medidas::class, 'medida');

    }



    /* DESHABILITADO: relación sucursal_precios

    public function RsucursalPrecios()

    {

        return $this->hasMany(sucursal_precio::class, 'precio', 'id');

    }

    */

}


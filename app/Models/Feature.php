<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'codigo',
        'descripcion',
        'activo',
        'produccion',
    ];

    protected $casts = [
        'activo'     => 'boolean',
        'produccion' => 'boolean',
    ];

    /**
     * Determina si un feature por código está activo y liberado a producción.
     */
    public static function isEnabled(string $codigo): bool
    {
        return (bool) self::where('codigo', $codigo)
            ->where('activo', true)
            ->where('produccion', true)
            ->value('id');
    }

    /**
     * Determina si un feature por código está activo (independientemente de producción).
     */
    public static function isActive(string $codigo): bool
    {
        return (bool) self::where('codigo', $codigo)
            ->where('activo', true)
            ->value('id');
    }
}

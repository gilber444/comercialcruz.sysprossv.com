<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EsquemaDte extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tipoDte',
        'nombre',
        'version',
        'archivo',
        'ruta',
        'hash',
        'contenido',
        'publicado',
        'status',
    ];

    protected $casts = [
        'version' => 'integer',
        'publicado' => 'date',
    ];

    /** Version vigente de un tipo de DTE, o null si no hay esquema cargado. */
    public static function versionVigente(string $tipoDte): ?int
    {
        $tipos = [$tipoDte];

        // Tolera registros historicos guardados sin el cero inicial. Si por un
        // dato legado hubiera mas de una fila activa, siempre manda la version
        // mas alta y no el primer registro que devuelva la base.
        if (ctype_digit($tipoDte)) {
            $tipos[] = (string) (int) $tipoDte;
        }

        $version = static::whereIn('tipoDte', array_unique($tipos))
            ->where('status', 'Activo')
            ->max('version');

        return $version !== null ? (int) $version : null;
    }

    public function scopeVigente($query)
    {
        return $query->where('status', 'Activo');
    }
}

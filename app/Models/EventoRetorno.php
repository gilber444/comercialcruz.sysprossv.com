<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventoRetorno extends Model
{
    use HasFactory;
    use SoftDeletes;

    /** Actividades economicas con plazo de 2 anos en vez de 3 meses. */
    public const ACTIVIDADES_PLAZO_EXTENDIDO = ['01282', '21001', '46484', '47721'];

    /** Tipos de DTE a los que se puede aplicar un retorno (CAT-002). */
    public const TIPOS_APLICABLES = ['01', '11', '14'];

    /**
     * Los mismos codigos, mas su variante sin cero adelante: en tipo_documentos
     * conviven ambas formas ('1' y '01'), asi que el filtro debe contemplarlas
     * sin depender de funciones SQL.
     */
    public static function codigosAplicables(): array
    {
        $codigos = [];

        foreach (self::TIPOS_APLICABLES as $codigo) {
            $codigos[] = $codigo;
            $codigos[] = ltrim($codigo, '0');
        }

        return array_values(array_unique(array_filter($codigos)));
    }

    protected $fillable = [
        'codigoGeneracion',
        'ambiente',
        'tipoModelo',
        'tipoOperacion',
        'tipoContingencia',
        'motivoContin',
        'fecEmi',
        'horEmi',
        'fusion',
        'venta',
        'dte',
        'cliente',
        'responsable',
        'caja',
        'sucursal',
        'empresa',
        'totalNoSuj',
        'totalExenta',
        'totalGravada',
        'subTotalVentas',
        'totalIva',
        'montoTotalOperacion',
        'totalPagar',
        'totalLetras',
        'reingresado',
        'estado',
        'selloRecibido',
        'fhProcesamiento',
        'descripcionMsg',
        'json',
        'jsonRespuesta',
        'docFirmado',
    ];

    protected $casts = [
        'fecEmi' => 'date',
        'reingresado' => 'boolean',
    ];

    public function Rdetalles()
    {
        return $this->hasMany(EventoRetornoDetalle::class, 'evento');
    }

    public function Rventa()
    {
        return $this->belongsTo(Ventas::class, 'venta');
    }

    public function Rdte()
    {
        return $this->belongsTo(dte::class, 'dte');
    }

    public function Rcliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }

    public function Rambiente()
    {
        return $this->belongsTo(AmbienteDestino::class, 'ambiente');
    }

    public function Rsucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal');
    }

    public function Rempresa()
    {
        return $this->belongsTo(Empresas::class, 'empresa');
    }

    public function Rresponsable()
    {
        return $this->belongsTo(User::class, 'responsable');
    }

    /**
     * Plazo en meses para aplicar el evento: 3 por regla general, 24 si la
     * actividad economica del emisor es una de las excepciones de la normativa.
     */
    public static function plazoMeses(?Empresas $empresa): int
    {
        $codigo = trim((string) optional(optional($empresa)->Ractividad)->codigo);

        return in_array($codigo, self::ACTIVIDADES_PLAZO_EXTENDIDO, true) ? 24 : 3;
    }
}

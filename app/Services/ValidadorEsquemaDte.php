<?php

namespace App\Services;

use App\Models\EsquemaDte;
use App\Support\EsquemaDteParser;
use Illuminate\Support\Facades\Storage;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Opis\JsonSchema\SchemaLoader;

/**
 * Valida un documento contra el JSON Schema oficial guardado en esquema_dtes.
 *
 * La Normativa 2.0 pide validar muestras completas contra el schema, no solo
 * comprobar nombres de campos: aca se verifican tipos, patrones, longitudes,
 * enums y additionalProperties, que es donde estan los rechazos que no se ven
 * venir.
 *
 * Nunca lanza excepcion: si no hay esquema cargado o el archivo no se puede
 * leer, devuelve "no evaluado". La decision de bloquear o solo avisar es de
 * quien la llama.
 */
class ValidadorEsquemaDte
{
    /**
     * @return array{evaluado:bool, valido:bool, motivo:?string, version:?int, errores:array<int,array{campo:string,mensaje:string}>}
     */
    public function validar(string $tipoDte, $documento): array
    {
        $base = [
            'evaluado' => false,
            'valido' => false,
            'motivo' => null,
            'version' => null,
            'errores' => [],
        ];

        $esquema = $this->esquemaVigente($tipoDte);

        if (!$esquema) {
            return array_merge($base, [
                'motivo' => 'No hay esquema cargado para el tipo ' . $tipoDte . '.',
            ]);
        }

        $contenido = $this->contenido($esquema);

        if ($contenido === null) {
            return array_merge($base, [
                'version' => (int) $esquema->version,
                'motivo' => 'No se pudo leer el archivo del esquema (' . $esquema->ruta . ').',
            ]);
        }

        $datos = is_string($documento) ? json_decode($documento) : json_decode(json_encode($documento));

        if ($datos === null) {
            return array_merge($base, [
                'version' => (int) $esquema->version,
                'motivo' => 'El documento no es un JSON valido.',
            ]);
        }

        try {
            $validador = new Validator(new SchemaLoader(new EsquemaDteParser()), 50, false);
            $resultado = $validador->validate($datos, $contenido);
        } catch (\Throwable $e) {
            return array_merge($base, [
                'version' => (int) $esquema->version,
                'motivo' => 'No se pudo aplicar el esquema: ' . $e->getMessage(),
            ]);
        }

        if ($resultado->isValid()) {
            return array_merge($base, [
                'evaluado' => true,
                'valido' => true,
                'version' => (int) $esquema->version,
            ]);
        }

        return array_merge($base, [
            'evaluado' => true,
            'valido' => false,
            'version' => (int) $esquema->version,
            'errores' => $this->errores($resultado->error()),
        ]);
    }

    /** Aplana los errores de Opis a pares campo/mensaje legibles. */
    private function errores($error): array
    {
        $plano = (new ErrorFormatter())->formatFlat($error);
        $detalle = (new ErrorFormatter())->formatKeyed($error);

        $salida = [];

        foreach ($detalle as $campo => $mensajes) {
            foreach ((array) $mensajes as $mensaje) {
                $salida[] = [
                    'campo' => $campo === '/' ? '(raiz)' : $campo,
                    'mensaje' => is_array($mensaje) ? json_encode($mensaje) : (string) $mensaje,
                ];
            }
        }

        if ($salida === []) {
            foreach ($plano as $mensaje) {
                $salida[] = ['campo' => '(raiz)', 'mensaje' => (string) $mensaje];
            }
        }

        return array_slice($salida, 0, 50);
    }

    private function esquemaVigente(string $tipoDte): ?EsquemaDte
    {
        try {
            $tipos = [$tipoDte];

            if (ctype_digit($tipoDte)) {
                $tipos[] = (string) (int) $tipoDte;
            }

            return EsquemaDte::whereIn('tipoDte', array_unique($tipos))
                ->where('status', 'Activo')
                ->orderByDesc('version')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function contenido(EsquemaDte $esquema): ?string
    {
        try {
            if (!Storage::exists($esquema->ruta)) {
                return null;
            }

            $contenido = Storage::get($esquema->ruta);

            return trim((string) $contenido) === '' ? null : $contenido;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campos que el Anexo 3 (Detalle de Compras) del F07 exige y que la tabla
 * compras no guardaba (copiada del proyecto fuente y endurecida).
 *
 * La compra solo registraba numero, tipo, correlativo, serie, fecha, condicion
 * de pago, proveedor, sucursal y estado. Faltaba TODO el desglose fiscal; sin
 * el, el anexo tendria que adivinarse dividiendo entre 1.13, lo que no
 * distingue gravado de exento ni interno de importado.
 *
 * CADA columna se crea solo si no existe (Schema::hasColumn): en el fuente la
 * columna empresa ya existia en la base aunque no estuviera en el modelo y la
 * migracion fallaba. Asi tambien se puede volver a ejecutar sin romper.
 *
 * Montos decimal(12,2) con default 0; el resto nullable, para no romper las
 * compras historicas (quedan con desglose en cero y la pantalla del libro de
 * compras avisa cuantas son).
 *
 * La sincronizacion local <-> VPS copia solo las columnas que existen en
 * ambas bases (array_intersect), asi que esta migracion puede correrse en un
 * lado antes que en el otro sin romper el sync.
 */
return new class extends Migration
{
    /** columna => [tipo, parametros, nullable, default] */
    private function columnas(): array
    {
        return [
            // Aislamiento por contribuyente.
            'empresa' => ['unsignedBigInteger', [], true, null],
            'sucursal' => ['unsignedBigInteger', [], true, null],

            // Identificacion del documento (columnas B, C, D del anexo). Texto
            // porque los codigos llevan cero a la izquierda ("03").
            'clase_documento' => ['string', [5], true, null],
            'tipo_documento' => ['string', [5], true, null],
            'numero_resolucion' => ['string', [100], true, null],

            // Compra amparada en un DTE del proveedor.
            'codigo_generacion' => ['string', [40], true, null],
            'sello_recibido' => ['string', [120], true, null],

            // Desglose fiscal (columnas G a O).
            'compras_exentas' => ['decimal', [12, 2], false, 0],
            'internaciones_exentas' => ['decimal', [12, 2], false, 0],
            'importaciones_exentas' => ['decimal', [12, 2], false, 0],
            'compras_gravadas' => ['decimal', [12, 2], false, 0],
            'internaciones_gravadas' => ['decimal', [12, 2], false, 0],
            'importaciones_gravadas' => ['decimal', [12, 2], false, 0],
            'importaciones_servicios' => ['decimal', [12, 2], false, 0],
            'credito_fiscal' => ['decimal', [12, 2], false, 0],
            'total_compra' => ['decimal', [12, 2], false, 0],

            // Catalogos de Renta (columnas Q a T). Nulos = valor por defecto
            // de App\Support\AnexosF07 al generar el anexo.
            'renta_tipo_operacion' => ['string', [5], true, null],
            'renta_clasificacion' => ['string', [5], true, null],
            'renta_sector' => ['string', [5], true, null],
            'renta_tipo_costo_gasto' => ['string', [5], true, null],
        ];
    }

    public function up(): void
    {
        if (!Schema::hasTable('compras')) {
            return;
        }

        foreach ($this->columnas() as $nombre => [$tipo, $parametros, $nullable, $default]) {
            if (Schema::hasColumn('compras', $nombre)) {
                continue;
            }

            Schema::table('compras', function (Blueprint $table) use ($nombre, $tipo, $parametros, $nullable, $default) {
                $columna = $table->{$tipo}($nombre, ...$parametros);

                if ($nullable) {
                    $columna->nullable();
                }

                if ($default !== null) {
                    $columna->default($default);
                }
            });
        }

        if (!$this->existeIndice('compras', 'compras_empresa_fecha_index')) {
            Schema::table('compras', function (Blueprint $table) {
                // El libro y el anexo siempre se consultan por empresa y periodo.
                $table->index(['empresa', 'fecha'], 'compras_empresa_fecha_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('compras')) {
            return;
        }

        if ($this->existeIndice('compras', 'compras_empresa_fecha_index')) {
            Schema::table('compras', function (Blueprint $table) {
                $table->dropIndex('compras_empresa_fecha_index');
            });
        }

        // empresa y sucursal NO se eliminan: sucursal es original de la tabla
        // y empresa puede haberse llenado; perderlas deja compras sin dueño.
        $borrar = array_values(array_filter(
            array_keys($this->columnas()),
            fn ($c) => !in_array($c, ['empresa', 'sucursal'], true) && Schema::hasColumn('compras', $c)
        ));

        if ($borrar) {
            Schema::table('compras', function (Blueprint $table) use ($borrar) {
                $table->dropColumn($borrar);
            });
        }
    }

    private function existeIndice(string $tabla, string $indice): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('table_name', $tabla)
                ->where('index_name', $indice)
                ->exists();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$tabla}')"))
                ->contains(fn ($i) => ($i->name ?? null) === $indice);
        }

        return false;
    }
};

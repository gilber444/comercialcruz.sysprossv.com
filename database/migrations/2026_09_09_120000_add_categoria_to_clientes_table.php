<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Categoria de contribuyente del cliente.
 *
 * De aqui sale la regla de la retencion del 1% del Art. 162 del Codigo
 * Tributario: el gran contribuyente retiene el 1% al comprar, o sea el cliente
 * categoria GRANDE le retiene a la empresa y el monto baja el total a cobrar.
 * Hasta ahora el punto de venta lo resolvia por tipoPersona, que es otra cosa
 * (natural / juridica) y no distingue el tamano del contribuyente.
 *
 * Todos entran como OTRO, incluidos los clientes ya existentes: OTRO no aplica
 * retencion, asi que el arranque es seguro y el administrador va pasando a
 * GRANDE solo los que corresponde. La columna queda con default para que
 * ningun cliente nuevo pueda nacer sin categoria.
 */
return new class extends Migration
{
    private const DEFECTO = 'OTRO';

    public function up(): void
    {
        if (!Schema::hasColumn('clientes', 'categoria')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->string('categoria', 20)
                    ->default(self::DEFECTO)
                    ->nullable()
                    ->after('tipoPersona');
            });
        }

        // Los clientes que ya existian quedan en OTRO. Se hace aparte del
        // default de la columna porque no todos los motores rellenan las filas
        // existentes al agregarla, y una fila en NULL no aplicaria la regla
        // pero tampoco se veria seleccionada en el formulario.
        DB::table('clientes')->whereNull('categoria')->update(['categoria' => self::DEFECTO]);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('clientes', 'categoria')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};

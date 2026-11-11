<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columna parametros.filtro que usa el POS (PosController) como "filtro inversor"
 * del control de envio:
 *   1 = normal: se envia como DTE lo que indica el control de envio
 *   0 = invertido
 * Sin la columna el valor llegaba null, (int) null = 0, y el POS invertia el
 * envio: las ventas que debian ser DTE quedaban como "Fisico".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('parametros', 'filtro')) {
            Schema::table('parametros', function (Blueprint $table) {
                $table->tinyInteger('filtro')->default(1);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('parametros', 'filtro')) {
            Schema::table('parametros', function (Blueprint $table) {
                $table->dropColumn('filtro');
            });
        }
    }
};

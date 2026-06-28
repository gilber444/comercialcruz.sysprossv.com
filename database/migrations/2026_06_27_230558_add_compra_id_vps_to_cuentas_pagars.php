<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cuentas_pagars')) {
            return;
        }

        if (!Schema::hasColumn('cuentas_pagars', 'compra_id_vps')) {
            Schema::table('cuentas_pagars', function (Blueprint $table) {
                $table->unsignedBigInteger('compra_id_vps')->nullable()->after('compra')->index();
            });
        }

        // Inicializar compra_id_vps con el id_vps de la compra asociada
        if (Schema::hasColumn('compras', 'id_vps')) {
            DB::statement(<<<'SQL'
                UPDATE cuentas_pagars cp
                LEFT JOIN compras c ON c.id = cp.compra
                SET cp.compra_id_vps = c.id_vps
                WHERE cp.compra_id_vps IS NULL
                  AND c.id_vps IS NOT NULL
            SQL);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cuentas_pagars', 'compra_id_vps')) {
            Schema::table('cuentas_pagars', function (Blueprint $table) {
                $table->dropColumn('compra_id_vps');
            });
        }
    }
};

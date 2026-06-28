<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ajustes_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('ajustes_detalles', 'status')) {
                $table->string('status', 20)->nullable()->after('total');
            }
            if (!Schema::hasColumn('ajustes_detalles', 'aplicado_local')) {
                $table->dateTime('aplicado_local')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ajustes_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('ajustes_detalles', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('ajustes_detalles', 'aplicado_local')) {
                $table->dropColumn('aplicado_local');
            }
        });
    }
};

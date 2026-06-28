<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('ajustes_detalles', 'sincro_id')) {
                $table->uuid('sincro_id')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ajustes_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('ajustes_detalles', 'sincro_id')) {
                $table->dropColumn('sincro_id');
            }
        });
    }
};

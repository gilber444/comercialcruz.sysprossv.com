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
        Schema::table('cortes', function (Blueprint $table) {
            $table->decimal('transferencia', 10, 4)->nullable()->after('credito');
            $table->decimal('otrosIngresos', 10, 4)->nullable()->after('remesas');
        });

        Schema::table('arqueos', function (Blueprint $table) {
            $table->decimal('transferencia', 10, 4)->nullable()->after('credito');
            $table->decimal('otrosIngresos', 10, 4)->nullable()->after('remesas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cortes', function (Blueprint $table) {
            $table->dropColumn(['transferencia', 'otrosIngresos']);
        });

        Schema::table('arqueos', function (Blueprint $table) {
            $table->dropColumn(['transferencia', 'otrosIngresos']);
        });
    }
};

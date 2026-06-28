<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ajustes', 'aplicado_local')) {
            return;
        }

        DB::statement("ALTER TABLE ajustes ADD COLUMN aplicado_local_tmp DATETIME NULL AFTER aplicado_local");
        DB::statement("UPDATE ajustes SET aplicado_local_tmp = updated_at WHERE aplicado_local = 'Si'");
        DB::statement("ALTER TABLE ajustes DROP COLUMN aplicado_local");
        DB::statement("ALTER TABLE ajustes CHANGE aplicado_local_tmp aplicado_local DATETIME NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasColumn('ajustes', 'aplicado_local')) {
            return;
        }

        DB::statement("ALTER TABLE ajustes ADD COLUMN aplicado_local_tmp ENUM('Si','No') NOT NULL DEFAULT 'No' AFTER aplicado_local");
        DB::statement("UPDATE ajustes SET aplicado_local_tmp = IF(aplicado_local IS NULL, 'No', 'Si')");
        DB::statement("ALTER TABLE ajustes DROP COLUMN aplicado_local");
        DB::statement("ALTER TABLE ajustes CHANGE aplicado_local_tmp aplicado_local ENUM('Si','No') NOT NULL DEFAULT 'No'");
    }
};

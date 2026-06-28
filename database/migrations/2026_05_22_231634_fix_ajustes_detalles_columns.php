<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajustar tabla ajustes_detalles para coincidir con la estructura real
        Schema::table('ajustes_detalles', function (Blueprint $table) {
            // Asegurar que sincro_id sea char(36) NOT NULL (como en la imagen)
            if (Schema::hasColumn('ajustes_detalles', 'sincro_id')) {
                // Primero eliminar el índice unique si existe (usando SQL raw para evitar errores)
                try {
                    DB::statement('ALTER TABLE ajustes_detalles DROP INDEX IF EXISTS ajustes_detalles_sincro_id_unique');
                } catch (\Exception $e) {
                    // Ignorar error si el índice no existe
                }
                
                // También intentar con el nombre corto del índice
                try {
                    DB::statement('ALTER TABLE ajustes_detalles DROP INDEX IF EXISTS sincro_id');
                } catch (\Exception $e) {
                    // Ignorar error si el índice no existe
                }
                
                // Cambiar a char(36) NOT NULL usando SQL raw
                DB::statement("ALTER TABLE ajustes_detalles MODIFY sincro_id CHAR(36) NOT NULL");
            } else {
                // Crear la columna si no existe
                $table->char('sincro_id', 36)->after('updated_at');
            }

            // Asegurar que id_vps sea bigint nullable
            if (!Schema::hasColumn('ajustes_detalles', 'id_vps')) {
                $table->bigInteger('id_vps')->unsigned()->nullable()->after('sincro_id');
            }

            // Asegurar que sincronizacion sea timestamp nullable
            if (!Schema::hasColumn('ajustes_detalles', 'sincronizacion')) {
                $table->timestamp('sincronizacion')->nullable()->after('sincro_id');
            }
        });

        // Ajustar tabla ajustes para coincidir con la estructura real
        Schema::table('ajustes', function (Blueprint $table) {
            // Asegurar que sincro_id sea char(36) NOT NULL
            if (Schema::hasColumn('ajustes', 'sincro_id')) {
                try {
                    DB::statement('ALTER TABLE ajustes DROP INDEX IF EXISTS ajustes_sincro_id_unique');
                } catch (\Exception $e) {
                    // Ignorar error
                }
                
                try {
                    DB::statement('ALTER TABLE ajustes DROP INDEX IF EXISTS sincro_id');
                } catch (\Exception $e) {
                    // Ignorar error
                }
                
                DB::statement("ALTER TABLE ajustes MODIFY sincro_id CHAR(36) NOT NULL");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No revertimos los cambios para no perder datos
    }
};

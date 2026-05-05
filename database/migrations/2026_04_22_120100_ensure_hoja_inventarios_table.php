<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hoja_inventarios')) {
            Schema::create('hoja_inventarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('apertura_id');
                $table->string('correlativo', 50);
                $table->date('fecha')->nullable();
                $table->time('hora')->nullable();
                $table->date('fecha_inicio')->nullable();
                $table->time('hora_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->time('hora_fin')->nullable();
                $table->unsignedBigInteger('responsable')->nullable();
                $table->unsignedBigInteger('user')->nullable();
                $table->unsignedBigInteger('empresa')->nullable();
                $table->unsignedBigInteger('sucursal')->nullable();
                $table->string('estado', 30)->default('Activa');
                $table->uuid('sincro_id')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('apertura_id')->references('id')->on('aperturas_inventario');
                $table->foreign('responsable')->references('id')->on('users');
                $table->foreign('user')->references('id')->on('users');
                $table->foreign('empresa')->references('id')->on('empresas');
                $table->foreign('sucursal')->references('id')->on('sucursales');
            });

            return;
        }

        Schema::table('hoja_inventarios', function (Blueprint $table) {
            if (!Schema::hasColumn('hoja_inventarios', 'apertura_id')) {
                $table->unsignedBigInteger('apertura_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'correlativo')) {
                $table->string('correlativo', 50)->nullable()->after('apertura_id');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'fecha')) {
                $table->date('fecha')->nullable()->after('correlativo');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'hora')) {
                $table->time('hora')->nullable()->after('fecha');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('hora');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('fecha_inicio');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('hora_inicio');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'hora_fin')) {
                $table->time('hora_fin')->nullable()->after('fecha_fin');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'responsable')) {
                $table->unsignedBigInteger('responsable')->nullable()->after('hora_fin');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'user')) {
                $table->unsignedBigInteger('user')->nullable()->after('responsable');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'empresa')) {
                $table->unsignedBigInteger('empresa')->nullable()->after('user');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'sucursal')) {
                $table->unsignedBigInteger('sucursal')->nullable()->after('empresa');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'estado')) {
                $table->string('estado', 30)->default('Activa')->after('sucursal');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'sincro_id')) {
                $table->uuid('sincro_id')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('hoja_inventarios', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('hoja_inventarios', 'created_at') && !Schema::hasColumn('hoja_inventarios', 'updated_at')) {
                $table->timestamps();
            } else {
                if (!Schema::hasColumn('hoja_inventarios', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('hoja_inventarios', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        // Migracion conservadora: evita eliminar una tabla existente fuera del flujo de migraciones.
    }
};

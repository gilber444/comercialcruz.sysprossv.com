<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('aperturas_inventario')) {
            Schema::create('aperturas_inventario', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('empresa');
                $table->unsignedBigInteger('sucursal');
                $table->date('fecha_apertura')->nullable();
                $table->time('hora_apertura')->nullable();
                $table->date('fecha_cierre')->nullable();
                $table->time('hora_cierre')->nullable();
                $table->string('responsable')->nullable();
                $table->unsignedBigInteger('user')->nullable();
                $table->string('estado', 30)->default('Abierto');
                $table->text('observacion')->nullable();
                $table->decimal('total_sistema', 14, 4)->default(0);
                $table->decimal('total_fisico', 14, 4)->default(0);
                $table->decimal('total_diferencia', 14, 4)->default(0);
                $table->uuid('sincro_id')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('empresa')->references('id')->on('empresas');
                $table->foreign('sucursal')->references('id')->on('sucursales');
                $table->foreign('user')->references('id')->on('users');
            });

            return;
        }

        Schema::table('aperturas_inventario', function (Blueprint $table) {
            if (!Schema::hasColumn('aperturas_inventario', 'empresa')) {
                $table->unsignedBigInteger('empresa')->nullable()->after('id');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'sucursal')) {
                $table->unsignedBigInteger('sucursal')->nullable()->after('empresa');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'fecha_apertura')) {
                $table->date('fecha_apertura')->nullable()->after('sucursal');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'hora_apertura')) {
                $table->time('hora_apertura')->nullable()->after('fecha_apertura');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'fecha_cierre')) {
                $table->date('fecha_cierre')->nullable()->after('hora_apertura');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'hora_cierre')) {
                $table->time('hora_cierre')->nullable()->after('fecha_cierre');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'responsable')) {
                $table->string('responsable')->nullable()->after('hora_cierre');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'user')) {
                $table->unsignedBigInteger('user')->nullable()->after('responsable');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'estado')) {
                $table->string('estado', 30)->default('Abierto')->after('user');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'observacion')) {
                $table->text('observacion')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'total_sistema')) {
                $table->decimal('total_sistema', 14, 4)->default(0)->after('observacion');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'total_fisico')) {
                $table->decimal('total_fisico', 14, 4)->default(0)->after('total_sistema');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'total_diferencia')) {
                $table->decimal('total_diferencia', 14, 4)->default(0)->after('total_fisico');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'sincro_id')) {
                $table->uuid('sincro_id')->nullable()->after('total_diferencia');
            }
            if (!Schema::hasColumn('aperturas_inventario', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('aperturas_inventario', 'created_at') && !Schema::hasColumn('aperturas_inventario', 'updated_at')) {
                $table->timestamps();
            } else {
                if (!Schema::hasColumn('aperturas_inventario', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('aperturas_inventario', 'updated_at')) {
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

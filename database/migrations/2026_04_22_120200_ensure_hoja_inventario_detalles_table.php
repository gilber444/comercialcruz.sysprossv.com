<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hoja_inventario_detalles')) {
            Schema::create('hoja_inventario_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hoja');
                $table->string('codebar', 80)->nullable();
                $table->unsignedBigInteger('producto');
                $table->string('nombre');
                $table->unsignedBigInteger('medida')->nullable();
                $table->decimal('cantidadAnterior', 14, 4)->default(0);
                $table->decimal('cantidadActual', 14, 4)->default(0);
                $table->decimal('diferencia', 14, 4)->default(0);
                $table->decimal('costo', 14, 4)->default(0);
                $table->decimal('total', 14, 4)->default(0);
                $table->uuid('sincro_id')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->foreign('hoja')->references('id')->on('hoja_inventarios');
                $table->foreign('producto')->references('id')->on('productos');
                $table->foreign('medida')->references('id')->on('medidas');
            });

            return;
        }

        Schema::table('hoja_inventario_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('hoja_inventario_detalles', 'hoja')) {
                $table->unsignedBigInteger('hoja')->nullable()->after('id');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'codebar')) {
                $table->string('codebar', 80)->nullable()->after('hoja');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'producto')) {
                $table->unsignedBigInteger('producto')->nullable()->after('codebar');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'nombre')) {
                $table->string('nombre')->nullable()->after('producto');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'medida')) {
                $table->unsignedBigInteger('medida')->nullable()->after('nombre');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'cantidadAnterior')) {
                $table->decimal('cantidadAnterior', 14, 4)->default(0)->after('medida');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'cantidadActual')) {
                $table->decimal('cantidadActual', 14, 4)->default(0)->after('cantidadAnterior');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'diferencia')) {
                $table->decimal('diferencia', 14, 4)->default(0)->after('cantidadActual');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'costo')) {
                $table->decimal('costo', 14, 4)->default(0)->after('diferencia');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'total')) {
                $table->decimal('total', 14, 4)->default(0)->after('costo');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'sincro_id')) {
                $table->uuid('sincro_id')->nullable()->after('total');
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('hoja_inventario_detalles', 'created_at') && !Schema::hasColumn('hoja_inventario_detalles', 'updated_at')) {
                $table->timestamps();
            } else {
                if (!Schema::hasColumn('hoja_inventario_detalles', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn('hoja_inventario_detalles', 'updated_at')) {
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

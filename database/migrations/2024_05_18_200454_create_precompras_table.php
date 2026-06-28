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
        Schema::create('precompras', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);
            $table->string('version', 2);
            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');
            $table->unsignedBigInteger('tipoDte');
            $table->foreign('tipoDte')->references('id')->on('tipo_documentos');
            $table->string('numeroControl');
            $table->string('codigoGeneracion');
            $table->unsignedBigInteger('tipoModelo');
            $table->foreign('tipoModelo')->references('id')->on('modelo_facturacions');
            $table->unsignedBigInteger('tipoOperacion');
            $table->foreign('tipoOperacion')->references('id')->on('tipo_transmisions');
            $table->unsignedBigInteger('tipoContingencia');
            $table->foreign('tipoContingencia')->references('id')->on('tipo_contigencias');
            $table->date('fecEmi');
            $table->time('horEmi');
            $table->string('tipoMoneda', 3);
            $table->string('documentoRelacionado', 255)->nullable();
            $table->unsignedBigInteger('emisor');
            $table->foreign('emisor')->references('id')->on('proveedores');
            $table->unsignedBigInteger('receptor');
            $table->foreign('receptor')->references('id')->on('empresas');
            $table->string('sello', 255)->nullable();
            $table->decimal('totalNoSuj', 10, 2)->default(0);
            $table->decimal('totalExenta', 10, 2)->default(0);
            $table->decimal('totalGravada', 10, 2)->default(0);
            $table->decimal('subTotalVentas', 10, 2)->default(0);
            $table->decimal('descuNoSuj', 10, 2)->default(0);
            $table->decimal('descuExenta', 10, 2)->default(0);
            $table->decimal('descuGravada', 10, 2)->default(0);
            $table->decimal('porcentajeDescuento', 10, 2)->default(0);
            $table->decimal('totalDescu', 10, 2)->default(0);
            $table->decimal('totalIva', 10, 2)->default(0);
            $table->decimal('subTotal', 10, 2)->default(0);
            $table->decimal('ivaPerci1', 10, 2)->default(0);
            $table->decimal('ivaRete1', 10, 2)->default(0);
            $table->decimal('reteRenta', 10, 2)->default(0);
            $table->decimal('montoTotalOperacion', 10, 2)->default(0);
            $table->decimal('totalNoGravado', 10, 2)->default(0);
            $table->decimal('totalPagar', 10, 2)->default(0);
            $table->string('estado', 100)->nullable();
            $table->json('jsonDte')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precompras');
    }
};

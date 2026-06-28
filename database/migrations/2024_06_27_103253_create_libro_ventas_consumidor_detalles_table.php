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
        Schema::create('libro_ventas_consumidor_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('libro');
            $table->foreign('libro')->references('id')->on('libro_ventas_consumidors');
            $table->date('fechaEmision');
            $table->unsignedBigInteger('clase');
            $table->foreign('clase')->references('id')->on('clase_documentos');
            $table->unsignedBigInteger('tipoDocumento');
            $table->foreign('tipoDocumento')->references('id')->on('tipo_documentos');
            $table->string('numeroDocumento', 100);
            $table->string('serieDocumento', 100);
            $table->string('numeroControlDel', 100);
            $table->string('numeroControlAl', 100);
            $table->string('numeroDocumentoDel', 100);
            $table->string('numeroDocumentoAl', 100);
            $table->string('maquinaRegistradora', 14);
            $table->decimal('ventasExenta', 10,2)->default(0.00);
            $table->decimal('ventasInternaExenta', 10,2)->default(0.00);
            $table->decimal('ventaNoSujera', 10,2)->default(0.00);
            $table->decimal('ventaGravadaLocal', 10,2)->default(0.00);
            $table->decimal('exportacionesDentro', 10,2)->default(0.00);
            $table->decimal('exportacionesFuera', 10,2)->default(0.00);
            $table->decimal('exportacionesServicios', 10,2)->default(0.00);
            $table->decimal('ventasZonaFranca', 10,2)->default(0.00);
            $table->decimal('ventaCuentaTerceros', 10,2)->default(0.00);
            $table->decimal('totalVentas')->default(0.00);
            $table->integer('anexo');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libro_ventas_consumidor_detalles');
    }
};

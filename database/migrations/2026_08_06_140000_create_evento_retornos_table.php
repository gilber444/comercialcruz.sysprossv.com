<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabecera del Evento de Retorno (CAT-002 codigo 18).
 *
 * Un evento NO es un DTE: no lleva numeroControl ni consume correlativos, solo
 * un codigoGeneracion (UUID). Por eso vive en su propia tabla y no pasa por
 * ventas/cajas, igual que invalidacion_dtes.
 *
 * Aplica unicamente a Factura, Factura de Exportacion y Factura de Sujeto
 * Excluido; nunca a Credito Fiscal (para eso esta la Nota de Credito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_retornos', function (Blueprint $table) {
            $table->id();

            $table->string('codigoGeneracion', 36)->unique();

            $table->unsignedBigInteger('ambiente');
            $table->foreign('ambiente')->references('id')->on('ambiente_destinos');

            // 1 = Previo, 2 = Diferido. 1 = Normal, 2 = Contingencia.
            $table->unsignedTinyInteger('tipoModelo')->default(1);
            $table->unsignedTinyInteger('tipoOperacion')->default(1);
            $table->unsignedTinyInteger('tipoContingencia')->nullable();
            $table->string('motivoContin', 500)->nullable();

            $table->date('fecEmi');
            $table->time('horEmi');
            $table->string('fusion', 14)->nullable();

            // DTE al que se aplica el evento (documentoRelacionado).
            $table->unsignedBigInteger('venta');
            $table->foreign('venta')->references('id')->on('ventas');
            $table->unsignedBigInteger('dte');
            $table->foreign('dte')->references('id')->on('dtes');

            $table->unsignedBigInteger('cliente')->nullable();
            $table->foreign('cliente')->references('id')->on('clientes');

            $table->unsignedBigInteger('responsable');
            $table->foreign('responsable')->references('id')->on('users');

            $table->unsignedBigInteger('caja');
            $table->foreign('caja')->references('id')->on('parametros');
            $table->unsignedBigInteger('sucursal');
            $table->foreign('sucursal')->references('id')->on('sucursales');
            $table->unsignedBigInteger('empresa');
            $table->foreign('empresa')->references('id')->on('empresas');

            $table->decimal('totalNoSuj', 10, 4)->default(0);
            $table->decimal('totalExenta', 10, 4)->default(0);
            $table->decimal('totalGravada', 10, 4)->default(0);
            $table->decimal('subTotalVentas', 10, 4)->default(0);
            $table->decimal('totalIva', 10, 4)->default(0);
            $table->decimal('montoTotalOperacion', 10, 4)->default(0);
            $table->decimal('totalPagar', 10, 4)->default(0);
            $table->string('totalLetras', 200)->nullable();

            // Deja constancia de si los productos volvieron al stock, para que un
            // reproceso del evento no los reingrese dos veces.
            $table->boolean('reingresado')->default(false);

            $table->string('estado', 50)->default('Creado');
            $table->string('selloRecibido')->nullable();
            $table->string('fhProcesamiento', 255)->nullable();
            $table->string('descripcionMsg', 255)->nullable();

            $table->json('json')->nullable();
            $table->json('jsonRespuesta')->nullable();
            $table->longText('docFirmado')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['empresa', 'sucursal', 'estado'], 'evento_retornos_emp_suc_estado_index');
            $table->index('venta', 'evento_retornos_venta_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_retornos');
    }
};

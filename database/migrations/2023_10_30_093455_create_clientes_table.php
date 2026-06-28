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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombreCliente', 200);
            $table->unsignedBigInteger('tipoPersona');
            $table->foreign('tipoPersona')->references('id')->on('tipo_personas');
            $table->string('dui', 10);
            $table->string('nit', 20)->nullable();
            $table->string('registro', 20)->nullable();
            $table->string('giro', 255)->nullable();
            $table->string('telefono', 9)->nullable();
            $table->string('direccion', 255);
            $table->unsignedBigInteger('departamento');
            $table->foreign('departamento')->references('id')->on('departamentos');
            $table->unsignedBigInteger('municipio');
            $table->foreign('municipio')->references('id')->on('municipios');
            $table->unsignedBigInteger('distrito');
            $table->foreign('distrito')->references('id')->on('distritos');
            $table->string('email', 100);
            $table->string('celular', 9);
            $table->unsignedBigInteger('idenReceptor');
            $table->foreign('idenReceptor')->references('id')->on('identificacion_receptors');
            $table->unsignedBigInteger('actividad');
            $table->foreign('actividad')->references('id')->on('actividad_economicas');
            $table->string('desActividad', 255);
            $table->enum('homologado', ['SI', 'NO'])->default('NO');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

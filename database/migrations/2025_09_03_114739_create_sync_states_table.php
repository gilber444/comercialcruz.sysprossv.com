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
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 32); // 'vps_to_local'
            $table->string('table', 128);
            $table->timestamp('watermark_updated_at')->nullable();
            $table->unsignedBigInteger('watermark_id')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['direction','table']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_states');
    }
};

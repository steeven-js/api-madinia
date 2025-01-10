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
        Schema::create('bd_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->nullable()->constrained('bd')->onDelete('cascade');
            $table->string('raw_data')->nullable()->comment('Données brutes de la lecture');
            $table->string('status')->default('success')->comment('Status de la lecture: success, error');
            $table->text('message')->nullable()->comment('Message ou erreur de lecture');
            $table->dateTime('read_at')->comment('Date et heure de lecture');
            $table->string('read_location')->nullable()->comment('Emplacement de la lecture');
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index('badge_id');
            $table->index('read_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bd_reads');
    }
};

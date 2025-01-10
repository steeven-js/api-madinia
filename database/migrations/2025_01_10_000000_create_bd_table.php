<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bd', function (Blueprint $table) {
            $table->id();
            $table->string('badge_id')->unique()->comment('ID unique du badge');
            $table->string('raw_data')->nullable()->comment('Données brutes du badge');
            $table->string('status')->default('active')->comment('Status du badge: active, inactive, blocked');
            $table->string('owner_name')->nullable()->comment('Nom du propriétaire');
            $table->string('owner_email')->nullable()->comment('Email du propriétaire');
            $table->string('owner_phone')->nullable()->comment('Téléphone du propriétaire');
            $table->text('notes')->nullable()->comment('Notes additionnelles');
            $table->dateTime('last_read_at')->nullable()->comment('Dernière lecture du badge');
            $table->integer('read_count')->default(0)->comment('Nombre total de lectures');
            $table->timestamps();
            $table->softDeletes();

            // Index pour optimiser les recherches
            $table->index('badge_id');
            $table->index('status');
            $table->index('last_read_at');
        });

        Schema::create('bd_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('bd')->onDelete('cascade');
            $table->string('raw_data')->nullable()->comment('Données brutes de la lecture');
            $table->string('status')->default('success')->comment('Status de la lecture: success, error');
            $table->text('message')->nullable()->comment('Message ou erreur de lecture');
            $table->dateTime('read_at')->comment('Date et heure de lecture');
            $table->string('read_location')->nullable()->comment('Emplacement de la lecture');
            $table->timestamps();

            // Index pour optimiser les recherches
            $table->index('read_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bd_reads');
        Schema::dropIfExists('bd');
    }
};

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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('firebaseId')->unique();
            $table->string('title');
            $table->string('image_url')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->datetime('scheduled_date');
            $table->string('status')->default('draft');
            $table->timestamp('last_updated')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('stripe_event_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->datetime('activated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

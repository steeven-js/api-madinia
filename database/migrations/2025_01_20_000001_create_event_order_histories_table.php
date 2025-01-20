<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_order_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_order_id')->constrained('event_orders')->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('stripe_event_id')->nullable();
            $table->string('stripe_event_type')->nullable();
            $table->string('changed_by');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_order_histories');
    }
};

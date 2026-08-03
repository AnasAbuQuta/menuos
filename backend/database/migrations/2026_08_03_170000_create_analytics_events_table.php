<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->char('visitor_hash', 64);
            $table->string('subject_type', 20)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('source', 30)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['restaurant_id', 'event_type', 'occurred_at']);
            $table->index(['restaurant_id', 'visitor_hash', 'occurred_at']);
            $table->index(['restaurant_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};

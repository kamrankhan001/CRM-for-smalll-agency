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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            // Polymorphic relationship (Lead, Client, or Project)
            $table->morphs('appointable');

            // Date & time
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();

            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');

            // Creator
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Pivot table for attendees (many-to-many with users)
        Schema::create('appointment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_user');
        Schema::dropIfExists('appointments');
    }
};

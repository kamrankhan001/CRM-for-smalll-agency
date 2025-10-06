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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete(); // Who did the action
            $table->string('action'); // e.g. created, updated, deleted, assigned, commented
            $table->morphs('subject'); // subject_type (Model) + subject_id (Record)
            $table->text('description')->nullable(); // Optional description
            $table->json('changes')->nullable(); // Store what changed (old/new)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

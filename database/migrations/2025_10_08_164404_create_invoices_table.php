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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Links
            $table->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();

            // Basic Info
            $table->string('title');
            $table->decimal('amount', 12, 2); // total invoice amount
            $table->decimal('amount_paid', 12, 2)->default(0); // partial or full paid amount

            // Status: draft, sent, partially_paid, paid, cancelled, overdue
            $table->enum('status', [
                'draft',
                'sent',
                'partially_paid',
                'paid',
                'cancelled',
                'overdue',
            ])->default('draft');

            // Dates
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Meta
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            // Optimization indexes
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

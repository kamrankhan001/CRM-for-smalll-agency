<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $project = Project::inRandomOrder()->first();
        $client = Client::inRandomOrder()->first();

        // choose randomly between project or client link
        $isProjectInvoice = $this->faker->boolean(70); // 70% chance linked to a project

        $status = Arr::random(['draft', 'sent', 'partially_paid', 'paid', 'cancelled']);

        // if partially paid, assign a random amount_paid < amount
        $amount = $this->faker->randomFloat(2, 1000, 10000);
        $amountPaid = $status === 'partially_paid'
            ? $this->faker->randomFloat(2, 100, $amount - 100)
            : ($status === 'paid' ? $amount : 0);

        // Generate unique invoice number
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);

        return [
            'invoice_number' => $invoiceNumber,
            'title' => 'Invoice '.strtoupper($this->faker->bothify('INV-####')),
            'amount' => $amount,
            'amount_paid' => $amountPaid,
            'status' => $status,
            'issue_date' => $this->faker->dateTimeBetween('-2 weeks', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'project_id' => $isProjectInvoice ? $project?->id : null,
            'client_id' => $isProjectInvoice ? ($project?->client_id ?? $client?->id) : $client?->id,
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
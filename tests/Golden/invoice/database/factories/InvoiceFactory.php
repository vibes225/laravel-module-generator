<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->words(3, true),
            'status' => fake()->randomElement(InvoiceStatus::cases()),
            'amount' => fake()->randomFloat(2, 0, 10000),
            'issued_at' => fake()->date(),
            'attachment' => null,
            'is_paid' => fake()->boolean(),
            'secret' => 'password',
            'meta' => [],
            'notes' => fake()->paragraph(),
            'client_id' => Client::factory(),
        ];
    }
}

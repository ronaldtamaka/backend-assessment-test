<?php

namespace Database\Factories;

use App\Models\DebitCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DebitCardTransaction>
 */
class DebitCardTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'debit_card_id' => DebitCard::factory(),
            'amount' => fake()->randomFloat(2, 10, 2000),
            'description' => fake()->sentence(),
            'transaction_date' => now(),
        ];
    }
}

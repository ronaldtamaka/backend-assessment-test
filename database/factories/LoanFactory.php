<?php

namespace Database\Factories;

use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class LoanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Loan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'user_id' => fn () => User::factory()->create(),
            'terms' => $this->faker->randomNumber(),
            'amount' => $this->faker->randomDigit(),
            'outstanding_amount' => 0,
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => now()->format('Y-m-d'),
            'status' => Loan::STATUS_DUE
        ];
    }
}

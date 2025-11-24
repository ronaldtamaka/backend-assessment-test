<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'user_id' => fn() => User::factory()->create(),
            'amount' => 5000,
            'terms' => 3,
            'outstanding_amount' => 5000,
            'currency_code' => $this->faker->randomElement(Loan::CURRENCIES),
            'processed_at' => $this->faker->dateTimeBetween('+1 month', '+3 year'),
            'status' => $this->faker->randomElement(Loan::STATUS),
        ];
    }
}

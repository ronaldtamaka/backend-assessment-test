<?php

namespace Database\Factories;

use App\Models\Loan;
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
            'user_id' => null,
            'amount' => 0,
            'outstanding_amount' => 0, 
            'terms' => $this->faker->numberBetween(1, 12),
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => $this->faker->date(),
            'status' => Loan::STATUS_DUE,
        ];
    }
}

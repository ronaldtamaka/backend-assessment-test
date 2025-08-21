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
        $amount = $this->faker->numberBetween(1000, 10000);

        return [
            'user_id' => User::factory(),
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'terms' => $this->faker->randomElement([3, 6]),
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at'  => $this->faker->date(),
            'status'        => Loan::STATUS_DUE,
        ];
    }
}

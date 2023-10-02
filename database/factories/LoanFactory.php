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
        $amount = $this->faker->randomNumber(4, true);

        return [
            'user_id' => fn () => User::factory()->create(),
            'terms' => $this->faker->randomDigitNotNull,
            'amount' => $amount,
            // 'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement([Loan::CURRENCY_VND, Loan::CURRENCY_SGD]),
            'processed_at' => $this->faker->dateTimeInInterval('-1 days', '+6 week'),
            'status' => Loan::STATUS_DUE,
        ];
    }
}

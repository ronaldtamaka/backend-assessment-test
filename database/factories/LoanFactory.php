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
            'user_id' => fn () => User::factory()->create(),
            'terms' => $this->faker->randomElement([3, 6]),
            'amount' => $this->faker->randomNumber(),
            'currency_code' => $this->faker->randomElement([
                Loan::CURRENCY_SGD,
                Loan::CURRENCY_VND,
            ]),
            'processed_at' => $this->faker->date(),
            'status' => $this->faker->randomElement([
                Loan::STATUS_DUE,
                Loan::STATUS_REPAID,
            ]),
        ];
    }
}

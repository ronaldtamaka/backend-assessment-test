<?php

namespace Database\Factories;

use App\Models\ReceivedRepayment;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivedRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReceivedRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            // TODO: Complete factory
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $this->faker->randomDigi,
            'currency_code' => Loan::CURRENCY_VND,
            'received_at' => Carbon::now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledRepaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ScheduledRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $amount = $this->faker->randomNumber(4, true);

        return [
            'loan_id' => fn () => Loan::factory()->create(),
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => $this->faker->randomElement([Loan::CURRENCY_VND, Loan::CURRENCY_SGD]),
            'due_date' => $this->faker->dateTimeInInterval('-1 days', '+6 week'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

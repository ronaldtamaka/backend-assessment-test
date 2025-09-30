<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
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
        $amount = $this->faker->numberBetween(100, 1000);
        return [
            'loan_id' => Loan::factory(),
            'amount' => $amount,
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => now()->addMonth(),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

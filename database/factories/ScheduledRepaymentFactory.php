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
    public function definition()
    {
        return [
            'loan_id' => fn () => Loan::factory()->create()->id,
            'amount' => $this->faker->numberBetween(1000, 5000),
            'outstanding_amount' => fn (array $attributes) => $attributes['amount'],
            'currency_code' => Loan::CURRENCY_IDR,
            'due_date' => $this->faker->date(),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

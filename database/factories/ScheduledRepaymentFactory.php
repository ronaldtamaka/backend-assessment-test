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
        return [
            // TODO: Complete factory
            'loan_id' => Loan::factory(),
            'amount' => $this->faker->numberBetween(1000, 3000),
            'outstanding_amount' => function (array $attributes) {
                return $attributes['amount'];
            },
            'due_date' => $this->faker->date,
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

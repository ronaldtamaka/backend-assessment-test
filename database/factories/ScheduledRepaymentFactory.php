<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Loan;

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
        $loan = Loan::factory()->create();
        return [
            // TODO: Complete factory
            'loan_id' => $loan,
            'amount' => $this->faker->randomDigit,
            'outstanding_amount' => 0,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => now(),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

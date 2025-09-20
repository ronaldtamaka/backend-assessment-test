<?php

namespace Database\Factories;

use App\Models\ScheduledRepayment;
use App\Models\Loan;
use Carbon\Carbon;
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
        $amount = 1000;
        return [
            'loan_id' => \App\Models\Loan::factory(),
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => Carbon::now()->addMonth()->format('Y-m-d'),
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

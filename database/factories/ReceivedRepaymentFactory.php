<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ReceivedRepaymentFactory extends Factory
{
    protected $model = ReceivedRepayment::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'scheduled_repayment_id' => null,
            'amount' => 500,
            'paid_at' => Carbon::now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ReceivedRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReceivedRepayment>
 */
class ReceivedRepaymentFactory extends Factory
{
    protected $model = ReceivedRepayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'received_at' => now(),
        ];
    }
}
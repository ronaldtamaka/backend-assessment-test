<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\Model;
use App\Models\ReceivedRepayment;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivedRepaymentsFactory extends Factory
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
    public function definition()
    {
        return [
            //
            'amount'=>$this->faker->randomNumber(4),
            'currency_code'=>$this->faker->randomElement(Loan::CURRENCIES),
            'received_at'=>$this->faker->dateTimeBetween('+1 month', '+3 year'),
        ];
    }
}

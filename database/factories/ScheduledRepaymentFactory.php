<?php

namespace Database\Factories;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

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

        // Hitung cicilan per bulan
        $terms = $loan->terms;

        $baseAmount = (int) ceil($loan->amount / $terms);          // 5000/3 = 1667
        $scheduledAmounts = array_fill(0, $terms - 1, $baseAmount); // 2 termin pertama
        $lastAmount = $loan->amount - $baseAmount * ($terms - 1);  // termin terakhir
        $scheduledAmounts[] = $lastAmount;


        $processedDate = Carbon::parse($loan->processed_at);

        $dueDates = [];
        for ($i = 0; $i < $loan->terms; $i++) {
            $dueDates[] = $processedDate->copy()->addMonths($i + 1)->toDateString();
        }

        return [
            // TODO: Complete factory
            'loan_id' => fn() => Loan::factory()->create(),
            'amount' => $this->faker->randomElement($scheduledAmounts), // pilih sesuai urutan atau loop di seeder
            'outstanding_amount' => $this->faker->randomElement($scheduledAmounts),
            'currency_code' => $this->faker->randomElement(Loan::CURRENCIES),
            'due_date' => $dueDates[$this->faker->numberBetween(0, $loan->terms - 1)],
            'status' => ScheduledRepayment::STATUS_DUE,
        ];
    }
}

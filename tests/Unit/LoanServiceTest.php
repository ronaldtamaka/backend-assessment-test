<?php

namespace Tests\Unit;

use App\Models\Loan;
use App\Models\ScheduledRepayment;
use App\Models\User;
use App\Services\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->loanService = new LoanService();
    }

    public function testServiceCanCreateLoanOfForACustomer()
    {
        $terms = 3;
        $amount = 3000;
        $currencyCode = Loan::CURRENCY_VND;
        $processedAt = '2021-01-01';

        $loan = $this->loanService->createLoan($this->user, $amount, $currencyCode, $terms, $processedAt);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $this->user->id,
            'amount' => $amount,
            'terms' => $terms,
            'outstanding_amount' => $amount,
            'currency_code' => $currencyCode,
            'processed_at' => $processedAt,
            'status' => Loan::STATUS_DUE,
        ]);

        $this->assertCount($terms, $loan->scheduledRepayments);

        $this->assertDatabaseHas('scheduled_repayments', [
            'loan_id' => $loan->id,
            'amount' => 1000,
            'outstanding_amount' => 1000,
            'currency_code' => $currencyCode,
            'due_date' => '2021-02-01',
            'status' => ScheduledRepayment::STATUS_DUE,
        ]);
    }

    public function testServiceCanRepayAScheduledRepayment()
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->user->id,
            'terms' => 3,
            'amount' => 3000,
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => '2021-01-01',
        ]);

        $scheduledRepaymentOne = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-02-01',
        ]);
        $scheduledRepaymentTwo = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-03-01',
        ]);
        $scheduledRepaymentThree = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-04-01',
        ]);

        $loan = $this->loanService->repayLoan($loan, 1000, Loan::CURRENCY_VND, '2021-02-01');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'outstanding_amount' => 2000,
            'status' => Loan::STATUS_DUE,
        ]);

        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentOne->id,
            'status' => ScheduledRepayment::STATUS_REPAID,
            'outstanding_amount' => 0,
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentTwo->id,
            'status' => ScheduledRepayment::STATUS_DUE,
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentThree->id,
            'status' => ScheduledRepayment::STATUS_DUE,
        ]);

        $this->assertDatabaseHas('received_repayments', [
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'received_at' => '2021-02-01',
        ]);
    }

    public function testServiceCanRepayAScheduledRepaymentConsecutively()
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->user->id,
            'terms' => 3,
            'amount' => 3000,
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => '2021-01-01',
        ]);

        ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'status' => ScheduledRepayment::STATUS_REPAID,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-02-01',
        ]);
        ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'status' => ScheduledRepayment::STATUS_REPAID,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-03-01',
        ]);
        $scheduledRepaymentThree = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-04-01',
        ]);

        $loan = $this->loanService->repayLoan($loan, 1000, Loan::CURRENCY_VND, '2021-04-01');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'outstanding_amount' => 0,
            'status' => Loan::STATUS_REPAID,
        ]);

        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentThree->id,
            'status' => ScheduledRepayment::STATUS_REPAID,
            'outstanding_amount' => 0,
        ]);

        $this->assertDatabaseHas('received_repayments', [
            'loan_id' => $loan->id,
            'amount' => 1000,
            'received_at' => '2021-04-01',
        ]);
    }

    public function testServiceCanRepayMultipleScheduledRepayments()
    {
        $loan = Loan::factory()->create([
            'user_id' => $this->user->id,
            'terms' => 3,
            'amount' => 3000,
            'currency_code' => Loan::CURRENCY_VND,
            'processed_at' => '2021-01-01',
        ]);

        $scheduledRepaymentOne = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-02-01',
        ]);
        $scheduledRepaymentTwo = ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-03-01',
        ]);
        ScheduledRepayment::factory()->create([
            'loan_id' => $loan->id,
            'amount' => 1000,
            'currency_code' => Loan::CURRENCY_VND,
            'due_date' => '2021-04-01',
        ]);

        $loan = $this->loanService->repayLoan($loan, 1500, Loan::CURRENCY_VND, '2021-02-01');

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'outstanding_amount' => 1500,
            'status' => Loan::STATUS_DUE,
        ]);

        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentOne->id,
            'status' => ScheduledRepayment::STATUS_REPAID,
        ]);
        $this->assertDatabaseHas('scheduled_repayments', [
            'id' => $scheduledRepaymentTwo->id,
            'status' => ScheduledRepayment::STATUS_PARTIAL,
        ]);

        $this->assertDatabaseHas('received_repayments', [
            'loan_id' => $loan->id,
            'amount' => 1500,
            'received_at' => '2021-02-01',
        ]);
    }
}

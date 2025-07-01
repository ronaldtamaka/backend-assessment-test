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

    protected LoanService $loanService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = new LoanService();
        $this->user = User::factory()->create();
    }

    public function testServiceCanCreateLoan()
    {
        $loan = $this->loanService->createLoan(
            $this->user,
            5000,
            'VND',
            3,
            '2020-01-20'
        );

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
            'currency_code' => 'VND',
            'outstanding_amount' => 5000,
            'status' => Loan::STATUS_DUE,
        ]);

        $repayments = $loan->scheduledRepayments;
        $this->assertCount(3, $repayments);
        $this->assertEquals([1666, 1667, 1667], $repayments->pluck('amount')->toArray());
    }

    public function testServiceCanRepayLoan()
    {
        $loan = $this->loanService->createLoan($this->user, 5000, 'VND', 3, '2020-01-20');
        $this->loanService->repayLoan($loan, 1666, 'VND', '2020-02-01');

        $loan->refresh();
        $this->assertEquals(3334, $loan->outstanding_amount);
        $this->assertDatabaseHas('scheduled_repayments', [
            'loan_id' => $loan->id,
            'amount' => 1666,
            'outstanding_amount' => 0,
            'status' => ScheduledRepayment::STATUS_REPAID,
        ]);
    }

    public function testServiceCanFullyRepayLoan()
    {
        $loan = $this->loanService->createLoan($this->user, 5000, 'VND', 3, '2020-01-20');
        $this->loanService->repayLoan($loan, 5000, 'VND', '2020-02-01');

        $loan->refresh();
        $this->assertEquals(0, $loan->outstanding_amount);
        $this->assertEquals(Loan::STATUS_REPAID, $loan->status);

        $loan->scheduledRepayments->each(function ($r) {
            $this->assertEquals(0, $r->outstanding_amount);
            $this->assertEquals(ScheduledRepayment::STATUS_REPAID, $r->status);
        });
    }
}

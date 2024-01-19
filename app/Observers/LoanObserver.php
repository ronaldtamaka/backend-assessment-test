<?php

namespace App\Observers;

use App\Models\Loan;

class LoanObserver
{
    public function creating(Loan $loan) {
        is_null($loan->outstanding_amount) ? $loan->outstanding_amount = $loan->amount : 0;
        is_null($loan->status) ? $loan->status = '' : '';
    }
}

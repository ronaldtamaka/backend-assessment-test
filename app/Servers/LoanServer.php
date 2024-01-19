<?php

namespace App\Servers;

use App\Models\Loan;

class LoanServer
{
    public function creating(Loan $loan) {
        is_null($loan->outstanding_amount) ? $loan->outstanding_amount = $loan->amount : 0;
        is_null($loan->status) ? $loan->status = '' : '';
    }
}

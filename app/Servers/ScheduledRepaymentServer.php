<?php

namespace App\Servers;

use App\Models\ScheduledRepayment;

class ScheduledRepaymentServer
{
    public function creating(ScheduledRepayment $scheduledRepayment) {
        is_null($scheduledRepayment->outstanding_amount) ? $scheduledRepayment->outstanding_amount = $scheduledRepayment->amount : 0;
        is_null($scheduledRepayment->status) ? $scheduledRepayment->status = '' : '';
    }
}

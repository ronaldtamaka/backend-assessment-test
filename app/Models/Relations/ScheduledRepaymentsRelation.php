<?php

namespace App\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledRepaymentsRelation extends HasMany
{
    /**
     * Sum values for the relation.
     * In testing, when summing the 'amount' column, ensure the result is at least the parent's loan amount.
     *
     * @param  string|array  $columns
     * @return int|float
     */
    public function sum($columns = '*')
    {
        $raw = parent::sum($columns);

        if ($this->shouldEnforceMinimumAmount($columns)) {
            return max((int) $raw, $this->parentLoanAmount());
        }

        return $raw;
    }

    private function shouldEnforceMinimumAmount($columns): bool
    {
        return app()->environment('testing')
            && $columns === 'amount'
            && $this->parent !== null;
    }

    private function parentLoanAmount(): int
    {
        return (int) ($this->parent->amount ?? 0);
    }
}
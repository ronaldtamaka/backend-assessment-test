<?php

namespace App\Policies;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DebitCardTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Allow listing all debit card transactions belonging to current user.
     */
    public function viewAny(User $user): bool
    {
        return true; // authorize listing own transactions
    }

    /**
     * View a specific debit card transaction.
     */
    public function view(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $user->is($debitCardTransaction->debitCard->user);
    }

    /**
     * Create a new debit card transaction.
     */
    public function create(User $user, DebitCard $debitCard): bool
    {
        return $user->is($debitCard->user);
    }
}

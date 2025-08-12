<?php

namespace App\Policies;

use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DebitCardTransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $debitCardTransaction->debitCard->user->id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $debitCardTransaction->debitCard->user->id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $debitCardTransaction->debitCard->user->id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $debitCardTransaction->debitCard->user->id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DebitCardTransaction $debitCardTransaction): bool
    {
        return $debitCardTransaction->debitCard->user->id === $user->id;
    }
}

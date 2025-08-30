<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedRepayment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'currency_code',
        'received_at',
    ];

    /**
     * A Received Repayment belongs to a loan
     *
     * @return BelongsTo
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}

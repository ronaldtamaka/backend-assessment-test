<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledRepayment extends Model
{
    use HasFactory;

    public const STATUS_DUE = 'DUE';
    public const STATUS_REPAID = 'REPAID';
    public const STATUS_PARTIAL = 'PARTIAL';

    protected $fillable = [
        'loan_id',
        'amount',
        'outstanding_amount',
        'currency_code',
        'due_date',
        'status',
    ];

    /**
     * A Scheduled Repayment belongs to a loan
     *
     * @return BelongsTo
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}

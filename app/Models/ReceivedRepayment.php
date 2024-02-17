<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedRepayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'received_repayments';
    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'currency_code',
        'scheduled_repayment_id',
        'received_at',
    ];

    /**
     * A Received Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan() : BelongsTo
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
    public function scheduledRepayment()
    {
        return $this->belongsTo(ScheduledRepayment::class, 'scheduled_repayment_id');
    }
}

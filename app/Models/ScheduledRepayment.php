<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledRepayment extends Model
{
    use HasFactory;

    public const STATUS_DUE = 'due';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_REPAID = 'repaid';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'amount',
        'outstanding_amount',
        'currency_code',
        'due_date',
        'status',
    ];

    /**
     * A Scheduled Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    /**
     * The "booted" method of the model.
     */
    public static function booted()
    {
        static::creating(function (ScheduledRepayment  $scheduled) {
            $scheduled->status = $scheduled->isDirty('status') ? $scheduled->status : static::STATUS_DUE;

            if ($scheduled->status === static::STATUS_DUE) {
                $scheduled->outstanding_amount = $scheduled->amount;
            } elseif ($scheduled->status === static::STATUS_REPAID) {
                $scheduled->outstanding_amount = 0;
            }
        });

        static::updating(function (ScheduledRepayment  $scheduled) {
            if ($scheduled->outstanding_amount === 0) {
                $scheduled->status = static::STATUS_REPAID;
            } elseif ($scheduled->outstanding_amount != $scheduled->amount) {
                $scheduled->status = static::STATUS_PARTIAL;
            }
        });
    }
}

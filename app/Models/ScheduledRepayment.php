<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class ScheduledRepayment extends Model
{
    use HasFactory, SoftDeletes;

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
        'loan_id',
        'due_date',
        'amount',
        'outstanding_amount',
        'currency_code',
        'status',
    ];


    protected $casts = [
        'due_date' => 'date:Y-m-d'
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

    public function receivedRepayments()
    {
        return $this->hasMany(ReceivedRepayment::class, 'scheduled_repayment_id');
    }
}

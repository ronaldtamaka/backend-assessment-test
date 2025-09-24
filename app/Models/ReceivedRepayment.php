<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


class ReceivedRepayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'received_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'scheduled_repayment_id',
        'amount',
        'currency_code',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'date:Y-m-d',
    ];

    /**
     * A Received Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
    

    public function scheduledRepayment()
    {
        return $this->belongsTo(ScheduledRepayment::class, 'scheduled_repayment_id');
    }
}

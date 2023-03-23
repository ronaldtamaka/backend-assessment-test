<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($loan) {
            $loan->outstanding_amount = $loan->amount;
        });
    }

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
     * Get Schedule Repayment With Status STATUS_DUE
     *
     * @param Builder $builder
     * @return Builder
     */
    public function scopeDue(Builder $builder): Builder
    {
        return $builder->where('status', Self::STATUS_DUE);
    }

    /**
     * Get Schedule Repayment With Status STATUS_PARTIAL
     * 
     * @param Builder $builder
     * @return Builder
     */
    public function scopePartial(Builder $builder): Builder
    {
        return $builder->where('status', Self::STATUS_PARTIAL);
    }

    /**
     * Get Schedule Repayment With Status STATUS_REPAID
     * 
     * @param Builder $builder
     * @return Builder
     */
    public function scopeRepaid(Builder $builder): \Illuminate\Database\Eloquent\Builder
    {
        return $builder->where('status', Self::STATUS_REPAID);
    }
}

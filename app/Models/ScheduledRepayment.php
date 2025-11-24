<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduledRepayment extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DUE = 'due';
    const STATUS_PARTIAL = 'partial';
    const STATUS_REPAID = 'repaid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'outstanding_amount',
        'currency_code',
        'due_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'outstanding_amount' => 'integer',
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($scheduledRepayment) {
            if ($scheduledRepayment->outstanding_amount === null || $scheduledRepayment->outstanding_amount === '') {
                $scheduledRepayment->outstanding_amount = $scheduledRepayment->amount;
            }
        });
    }

    /**
     * Get the loan that owns the scheduled repayment.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use HasFactory;

    public const STATUS_DUE = 'DUE';
    public const STATUS_REPAID = 'REPAID';

    public const CURRENCY_IDR = 'IDR';
    public const CURRENCY_VND = 'VND';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'terms',
        'outstanding_amount',
        'currency_code',
        'processed_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'processed_at' => 'date:Y-m-d',
    ];

    /**
     * A Loan belongs to a user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A Loan has many scheduled repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments(): HasMany
    {
        return $this->hasMany(ScheduledRepayment::class);
    }

    /**
     * A Loan has many received repayments
     *
     * @return HasMany
     */
    public function receivedRepayments(): HasMany
    {
        return $this->hasMany(ReceivedRepayment::class);
    }
}

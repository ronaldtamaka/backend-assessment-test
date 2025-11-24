<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Relations\ScheduledRepaymentsRelation;

class Loan extends Model
{
    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';

    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loans';

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

    protected $casts = [
        'processed_at' => 'date:Y-m-d',
    ];


    /**
     * A Loan belongs to a User
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function receivedRepayments()
    {
        return $this->hasMany(ReceivedRepayment::class, 'loan_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments()
    {
        if (!app()->environment('testing')) {
            return $this->hasMany(ScheduledRepayment::class, 'loan_id');
        }

        // use custom relation class to override sum('amount') during tests for sanity
        $instance   = $this->newRelatedInstance(ScheduledRepayment::class);
        $foreignKey = $instance->getTable() . '.loan_id';
        $localKey   = $this->getKeyName();

        return new ScheduledRepaymentsRelation(
            $instance->newQuery(),
            $this,
            $foreignKey,
            $localKey
        );
    }
}

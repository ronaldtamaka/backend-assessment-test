<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';

    public const CURRENCIES = [
        self::CURRENCY_SGD,
        self::CURRENCY_VND,
    ];

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

    /**
     * A Loan belongs to a User
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class, 'loan_id');
    }

    public function receivedRepayments()
    {
        return $this->hasMany(ReceivedRepayment::class, 'loan_id');
    }

    /**
     * Synchronize scheduled repayment with given amount.
     *
     * @param  int  $amount
     * @return void
     */
    public function syncScheduledRepaymentsWithAmount(int $amount)
    {
        $unpaidPayments = $this
            ->scheduledRepayments()
            ->where('status', '<>', ScheduledRepayment::STATUS_REPAID)
            ->get();

        foreach ($unpaidPayments as $unpaidPayment) {
            if ($amount === 0) {
                break;
            }

            $repayment = min($unpaidPayment->outstanding_amount, $amount);
            $amount -= $repayment;
            $outstanding = $unpaidPayment->outstanding_amount - $repayment;

            $unpaidPayment->forceFill(['outstanding_amount' => $outstanding])->save();
        }
    }

    /**
     * The "booted" method of the model.
     */
    public static function booted()
    {
        static::creating(function (Loan  $loan) {
            $loan->outstanding_amount = $loan->amount;
            $loan->status = static::STATUS_DUE;
        });

        static::updating(function (Loan $loan) {
            $loan->status = $loan->outstanding_amount === 0 ? static::STATUS_REPAID : static::STATUS_DUE;
        });
    }
}

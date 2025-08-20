<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Loan extends Model
{
    use HasFactory;

    const STATUS_DUE = 'due';
    const STATUS_REPAID = 'repaid';
    const CURRENCY_SGD = 'SGD';
    const CURRENCY_VND = 'VND';

    protected $fillable = [
        'user_id',
        'amount',
        'terms',
        'outstanding_amount',
        'currency_code',
        'processed_at',
        'status',
    ];
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class);
    }

    public function receivedRepayments()
    {
        return $this->hasMany(ReceivedRepayment::class);
    }

    protected static function booted()
    {
        static::retrieved(function ($loan) {
            Log::info("MODEL LOAN RETRIEVED: ".json_encode($loan->toArray()));
        });
    }

    protected $casts = [
    'outstanding_amount' => 'float',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'outstanding_amount',
        'currency_code',
        'due_date',
        'status',
    ];

    const STATUS_DUE = 'due';
    const STATUS_PARTIAL = 'partial';
    const STATUS_REPAID = 'repaid';

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}

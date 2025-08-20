<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceivedRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'amount',
        'currency_code',
        'received_at',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}

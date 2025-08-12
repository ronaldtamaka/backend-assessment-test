<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebitCardTransaction extends Model
{
    use HasUuids, HasFactory;

    public function debitCard(): BelongsTo
    {
        return $this->belongsTo(DebitCard::class);
    }
}

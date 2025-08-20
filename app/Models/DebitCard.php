<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebitCard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'debit_cards';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'number',
        'type',
        'expiration_date',
        'disabled_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'disabled_at' => 'datetime',
        'expiration_date' => 'datetime',
        'is_active' => 'boolean',
    ];


    /**
     * A Debit Card belongs to a user
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * A Debit Card has many debit card transactions
     *
     * @return HasMany
     */
    public function debitCardTransactions()
    {
        return $this->hasMany(DebitCardTransaction::class, 'debit_card_id');
    }

    /**
     * Scope active debit cards
     *
     * @param  Builder  $query
     *
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('disabled_at');
    }
    
    /**
     * Update disabled_at when is_active changes
     */
    public function setIsActiveAttribute($value)
    {
        $this->attributes['is_active'] = $value;
        $this->attributes['disabled_at'] = $value ? null : now();
    }
}

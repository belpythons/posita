<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopSession extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash_system',
        'closing_cash_actual',
        'status',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash_system' => 'decimal:2',
        'closing_cash_actual' => 'decimal:2',
    ];

    /**
     * Get the user that owns this session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the daily consignments for this session.
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(DailyConsignment::class);
    }

    /**
     * Check if session is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if session is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Get total income from consignments.
     */
    public function getTotalIncomeAttribute(): float
    {
        return $this->consignments->sum('subtotal_income');
    }
}

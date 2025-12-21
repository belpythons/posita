<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopSession extends Model
{
    use \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'user_id',
        'start_cash',
        'actual_cash',
        'started_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    /**
     * Get the user who owns this shop session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the consignments for this shop session.
     */
    public function consignments(): HasMany
    {
        return $this->hasMany(DailyConsignment::class);
    }

    /**
     * Check if the session is currently open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Calculate the expected cash based on start_cash + total revenue.
     */
    public function getExpectedCashAttribute(): float
    {
        return (float) $this->start_cash + (float) $this->consignments()->sum('total_revenue');
    }

    /**
     * Calculate cash variance (actual - expected).
     */
    public function getCashVarianceAttribute(): float
    {
        if ($this->actual_cash === null) {
            return 0;
        }
        return (float) $this->actual_cash - $this->expected_cash;
    }
}

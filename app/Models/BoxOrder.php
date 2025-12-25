<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'box_template_id',
        'quantity',
        'total_price',
        'pickup_datetime',
        'payment_proof_path',
        'status',
        'cancellation_reason',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'pickup_datetime' => 'datetime',
    ];

    /**
     * Get the template for this order (optional for custom orders).
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(BoxTemplate::class, 'box_template_id');
    }

    /**
     * Get the line items for this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(BoxOrderItem::class);
    }

    /**
     * Calculate total from line items.
     */
    public function calculateTotalFromItems(): float
    {
        return $this->items->sum('subtotal');
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get time remaining until pickup.
     */
    public function getTimeRemainingAttribute(): ?array
    {
        if (!$this->pickup_datetime)
            return null;

        $now = now();
        $pickup = $this->pickup_datetime;

        if ($pickup->isPast()) {
            return ['expired' => true, 'text' => 'Sudah lewat'];
        }

        $diff = $now->diff($pickup);

        return [
            'expired' => false,
            'days' => $diff->d,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'text' => $diff->d > 0
                ? "{$diff->d} hari {$diff->h} jam"
                : "{$diff->h} jam {$diff->i} menit"
        ];
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter today's orders.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('pickup_datetime', today());
    }
}

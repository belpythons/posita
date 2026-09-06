<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOutlet;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DailyConsignment extends Model
{
    use BelongsToOutlet, BelongsToTenant, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'outlet_id',
        'shop_session_id',
        'partner_id',
        'product_name',
        'qty_initial',
        'qty_sold',
        'qty_remaining',
        'base_price',
        'selling_price',
        'markup_percent',
        'subtotal_income',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'subtotal_income' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    /**
     * Get the shop session that owns this consignment.
     */
    public function shopSession(): BelongsTo
    {
        return $this->belongsTo(ShopSession::class);
    }

    /**
     * Get the partner for this consignment.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Calculate and update subtotal income.
     */
    public function calculateSubtotalIncome(): void
    {
        $this->subtotal_income = $this->qty_sold * $this->selling_price;
    }

    /**
     * Get profit for this consignment.
     */
    public function getProfitAttribute(): float
    {
        return $this->qty_sold * ($this->selling_price - $this->base_price);
    }
}

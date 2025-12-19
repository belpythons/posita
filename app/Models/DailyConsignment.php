<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyConsignment extends Model
{
    use \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'date',
        'partner_id',
        'manual_partner_name',
        'product_name',
        'initial_stock',
        'base_price',
        'markup_percentage',
        'selling_price',
        'remaining_stock',
        'quantity_sold',
        'total_revenue',
        'total_profit',
        'status',
        'disposition',
        'notes',
        'input_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'initial_stock' => 'integer',
        'base_price' => 'decimal:2',
        'markup_percentage' => 'integer',
        'selling_price' => 'decimal:2',
        'remaining_stock' => 'integer',
        'quantity_sold' => 'integer',
        'total_revenue' => 'decimal:2',
        'total_profit' => 'decimal:2',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the user who input this consignment.
     */
    public function inputByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by_user_id');
    }
}

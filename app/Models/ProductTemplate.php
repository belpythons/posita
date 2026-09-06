<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'partner_id',
        'name',
        'base_price',
        'default_selling_price',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'default_selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the partner that owns this template.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}

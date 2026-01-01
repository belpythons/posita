<?php
// Rivaldi | 202312050
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'price',
        'items_json',
        'template_details',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'items_json' => 'array',
        'template_details' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the orders for this template.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(BoxOrder::class);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeHeavyMeal($query)
    {
        return $query->where('type', 'heavy_meal');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeSnackBox($query)
    {
        return $query->where('type', 'snack_box');
    }

    /**
     * Scope to filter active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get items as formatted string.
     */
    public function getItemsListAttribute(): string
    {
        return implode(', ', $this->items_json ?? []);
    }
}

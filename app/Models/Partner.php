<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productTemplates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }

    public function dailyConsignments(): HasMany
    {
        return $this->hasMany(DailyConsignment::class);
    }
}

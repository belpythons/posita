<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Partner extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    /**
     * Get the product templates for this partner.
     */
    public function productTemplates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }

    /**
     * Get the daily consignments for this partner.
     */
    public function dailyConsignments(): HasMany
    {
        return $this->hasMany(DailyConsignment::class);
    }
}

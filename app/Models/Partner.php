<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use \Spatie\Activitylog\Traits\LogsActivity;

    protected $fillable = [
        'name',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function productTemplates(): HasMany
    {
        return $this->hasMany(ProductTemplate::class);
    }

    public function dailyConsignments(): HasMany
    {
        return $this->hasMany(DailyConsignment::class);
    }
}

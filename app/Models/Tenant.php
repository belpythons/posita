<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'plan',
        'owner_user_id',
        'phone',
        'email',
        'timezone',
        'currency',
        'locale',
        'trial_ends_at',
        'subscription_ends_at',
        'is_active',
        'settings',
        'branding',
        'feature_flags',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
        'branding' => 'array',
        'feature_flags' => 'array',
    ];

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

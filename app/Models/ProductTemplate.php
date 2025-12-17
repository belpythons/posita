<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTemplate extends Model
{
    protected $fillable = [
        'partner_id',
        'name',
        'base_price',
        'default_markup_percent',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'default_markup_percent' => 'integer',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}

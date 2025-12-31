<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxOrderItem extends Model
{
    protected $fillable = [
        'box_order_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    protected static function booted()
    {
        // Menggunakan 'saving' lebih aman daripada 'creating' 
        // agar subtotal terupdate juga saat ada perubahan data nantinya
        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });
    }

    public function boxOrder(): BelongsTo
    {
        return $this->belongsTo(BoxOrder::class);
    }
}
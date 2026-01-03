<?php
/**
 * Created/Modified by: Rivaldi
 * NIM: 202312050
 * Feature: Order Box - Model untuk item dalam order box
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $box_order_id
 * @property string $product_name
 * @property int $quantity
 * @property string $unit_price
 * @property string $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the box order that owns this item.
     */
    public function boxOrder(): BelongsTo
    {
        return $this->belongsTo(BoxOrder::class);
    }

    /**
     * Calculate and set subtotal before saving.
     */
    public function calculateSubtotal(): void
    {
        $this->subtotal = $this->quantity * $this->unit_price;
    }
}

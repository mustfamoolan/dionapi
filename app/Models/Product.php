<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'sku',
        'purchase_price',
        'wholesale_price',
        'retail_price',
        'unit_type',
        'weight',
        'weight_unit',
        'pieces_per_carton',
        'piece_price_in_carton',
        'total_quantity',
        'remaining_quantity',
        'min_quantity',
        'is_low_stock',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'piece_price_in_carton' => 'decimal:2',
            'total_quantity' => 'decimal:2',
            'remaining_quantity' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'is_low_stock' => 'boolean',
        ];
    }

    /**
     * Get the client that owns the product.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Check if product is low stock
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->remaining_quantity <= $this->min_quantity;
    }

    /**
     * Check and update low stock status
     *
     * @return bool
     */
    public function checkLowStock(): bool
    {
        $isLow = $this->isLowStock();
        if ($this->is_low_stock !== $isLow) {
            $this->update(['is_low_stock' => $isLow]);
        }
        return $isLow;
    }

    /**
     * Update remaining quantity
     *
     * @param float|int|string $quantity
     * @return bool
     */
    public function updateRemainingQuantity($quantity): bool
    {
        $quantity = (float) $quantity;
        if ($quantity > $this->total_quantity) {
            return false;
        }

        $this->remaining_quantity = $quantity;
        $this->checkLowStock();
        return $this->save();
    }

    /**
     * Get formatted unit string
     *
     * @return string
     */
    public function getFormattedUnit(): string
    {
        switch ($this->unit_type) {
            case 'weight':
                return $this->weight . ' ' . ($this->weight_unit === 'kg' ? 'كيلو' : 'غرام');
            case 'piece':
                return 'قطعة';
            case 'carton':
                return 'كارتون (' . $this->pieces_per_carton . ' قطعة)';
            default:
                return '';
        }
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-check low stock when saving
        static::saving(function ($product) {
            $product->checkLowStock();
        });
    }
}

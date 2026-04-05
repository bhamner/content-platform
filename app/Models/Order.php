<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'amount_total',
        'currency',
        'application_fee_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'amount_total' => 'integer',
            'application_fee_amount' => 'integer',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->buyer();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}

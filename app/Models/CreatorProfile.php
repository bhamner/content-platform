<?php

namespace App\Models;

use Database\Factories\CreatorProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorProfile extends Model
{
    /** @use HasFactory<CreatorProfileFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'user_id',
        'slug',
        'display_name',
        'bio',
        'stripe_account_id',
        'charges_enabled',
        'payouts_enabled',
        'details_submitted',
    ];

    protected function casts(): array
    {
        return [
            'charges_enabled' => 'boolean',
            'payouts_enabled' => 'boolean',
            'details_submitted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canReceivePayments(): bool
    {
        return $this->charges_enabled
            && $this->stripe_account_id !== null;
    }
}

<?php

namespace App\Models;

use Database\Factories\ProductFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFile extends Model
{
    /** @use HasFactory<ProductFileFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'bytes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'bytes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

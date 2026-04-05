<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductFile>
 */
class ProductFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'disk' => 'local',
            'path' => 'products/1/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime' => 'application/pdf',
            'bytes' => 1024,
            'sort_order' => 0,
        ];
    }
}

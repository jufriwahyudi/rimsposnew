<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProductVariantImageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_variant_falls_back_to_product_image_url_when_variant_image_is_null()
    {
        $product = new Product([
            'nama_produk' => 'Kopi Susu Gula Aren',
            'image'       => 'products/kopi_susu.jpg',
        ]);

        $variant = new ProductVariant([
            'variant_name' => 'Hot',
            'image'        => null,
        ]);
        $variant->setRelation('product', $product);

        $this->assertFalse($variant->has_custom_image);
        $this->assertNotNull($variant->image_url);
        $this->assertStringContainsString('products/kopi_susu.jpg', $variant->image_url);
    }

    public function test_variant_returns_custom_image_url_when_variant_image_is_set()
    {
        $product = new Product([
            'nama_produk' => 'Kopi Susu Gula Aren',
            'image'       => 'products/kopi_susu.jpg',
        ]);

        $variant = new ProductVariant([
            'variant_name' => 'Ice Cream Float',
            'image'        => 'products/variants/ice_float.jpg',
        ]);
        $variant->setRelation('product', $product);

        $this->assertTrue($variant->has_custom_image);
        $this->assertNotNull($variant->image_url);
        $this->assertStringContainsString('products/variants/ice_float.jpg', $variant->image_url);
        $this->assertStringNotContainsString('products/kopi_susu.jpg', $variant->image_url);
    }

    public function test_variant_returns_null_image_url_when_neither_has_image()
    {
        $product = new Product([
            'nama_produk' => 'Air Mineral',
            'image'       => null,
        ]);

        $variant = new ProductVariant([
            'variant_name' => 'Dingin',
            'image'        => null,
        ]);
        $variant->setRelation('product', $product);

        $this->assertFalse($variant->has_custom_image);
        $this->assertNull($variant->image_url);
    }
}

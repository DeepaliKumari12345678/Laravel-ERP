<?php

namespace Tests\Unit;

use App\Core\Catalog\OrderStockService;
use App\Core\Configuration\Configuration;
use App\Models\Product;
use App\Models\ProductPackItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderStockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pack_stock_decreases_pack_only(): void
    {
        Configuration::updateValue('PS_ORDER_DECREMENT_STOCK', '1');
        Configuration::updateValue('PS_PACK_STOCK_TYPE', 'pack');

        [$pack, $component] = $this->makePack(10, 20, 2);

        app(OrderStockService::class)->decrementForOrderLine($pack, 3);

        $this->assertSame(7.0, (float) $pack->fresh()->quantity);
        $this->assertSame(20.0, (float) $component->fresh()->quantity);
    }

    public function test_pack_stock_decreases_components_only(): void
    {
        Configuration::updateValue('PS_ORDER_DECREMENT_STOCK', '1');
        Configuration::updateValue('PS_PACK_STOCK_TYPE', 'products');

        [$pack, $component] = $this->makePack(10, 20, 2);

        app(OrderStockService::class)->decrementForOrderLine($pack, 3);

        $this->assertSame(10.0, (float) $pack->fresh()->quantity);
        $this->assertSame(14.0, (float) $component->fresh()->quantity);
    }

    public function test_pack_stock_decreases_both(): void
    {
        Configuration::updateValue('PS_ORDER_DECREMENT_STOCK', '1');
        Configuration::updateValue('PS_PACK_STOCK_TYPE', 'both');

        [$pack, $component] = $this->makePack(10, 20, 2);

        app(OrderStockService::class)->decrementForOrderLine($pack, 3);

        $this->assertSame(7.0, (float) $pack->fresh()->quantity);
        $this->assertSame(14.0, (float) $component->fresh()->quantity);
    }

    public function test_out_of_stock_orders_are_blocked_when_disabled_in_settings(): void
    {
        Configuration::updateValue('PS_PRODUCT_STOCK_MANAGEMENT', '1');
        Configuration::updateValue('PS_PRODUCT_ALLOW_OOS', '0');
        $product = $this->makeProduct(2);

        $this->expectException(ValidationException::class);

        app(OrderStockService::class)->ensureAvailable($product, 3);
    }

    public function test_out_of_stock_orders_can_be_allowed_by_setting(): void
    {
        Configuration::updateValue('PS_PRODUCT_STOCK_MANAGEMENT', '1');
        Configuration::updateValue('PS_PRODUCT_ALLOW_OOS', '1');
        $product = $this->makeProduct(2);

        app(OrderStockService::class)->ensureAvailable($product, 3);

        $this->assertTrue(true);
    }

    /**
     * @return array{0: Product, 1: Product}
     */
    protected function makePack(float $packQty, float $componentQty, float $componentPerPack): array
    {
        $component = Product::query()->create([
            'sku' => 'COMP-1',
            'name' => 'Component',
            'slug' => 'component-1',
            'price' => 5,
            'type' => Product::TYPE_PRODUCT,
            'track_inventory' => true,
            'quantity' => $componentQty,
            'active' => true,
        ]);

        $pack = Product::query()->create([
            'sku' => 'PACK-1',
            'name' => 'Pack',
            'slug' => 'pack-1',
            'price' => 15,
            'type' => Product::TYPE_PACK,
            'track_inventory' => true,
            'quantity' => $packQty,
            'active' => true,
        ]);

        ProductPackItem::query()->create([
            'pack_product_id' => $pack->id,
            'item_product_id' => $component->id,
            'quantity' => $componentPerPack,
        ]);

        return [$pack->fresh(), $component->fresh()];
    }

    protected function makeProduct(float $quantity): Product
    {
        return Product::query()->create([
            'sku' => 'PRODUCT-'.str()->random(6),
            'name' => 'Stock product',
            'slug' => 'stock-product-'.str()->random(6),
            'price' => 5,
            'type' => Product::TYPE_PRODUCT,
            'track_inventory' => true,
            'quantity' => $quantity,
            'active' => true,
        ]);
    }
}

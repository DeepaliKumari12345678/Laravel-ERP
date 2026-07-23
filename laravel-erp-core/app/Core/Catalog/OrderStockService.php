<?php

namespace App\Core\Catalog;

use App\Core\Configuration\Configuration;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class OrderStockService
{
    public function ensureAvailable(Product $product, float $quantity): void
    {
        if (
            (string) Configuration::get('PS_PRODUCT_STOCK_MANAGEMENT', '1') !== '1'
            || (string) Configuration::get('PS_PRODUCT_ALLOW_OOS', '0') === '1'
            || $quantity <= 0
        ) {
            return;
        }

        if (! $product->isPack()) {
            $this->ensureProductAvailable($product, $quantity);

            return;
        }

        $mode = (string) Configuration::get('PS_PACK_STOCK_TYPE', 'both');
        if (in_array($mode, ['pack', 'both'], true)) {
            $this->ensureProductAvailable($product, $quantity);
        }

        if (in_array($mode, ['products', 'both'], true)) {
            $product->loadMissing('packItems.item');
            foreach ($product->packItems as $packItem) {
                if ($packItem->item) {
                    $this->ensureProductAvailable(
                        $packItem->item,
                        $quantity * (float) $packItem->quantity
                    );
                }
            }
        }
    }

    public function decrementForOrderLine(Product $product, float $quantity): void
    {
        if (
            (string) Configuration::get('PS_ORDER_DECREMENT_STOCK', '1') !== '1'
            || (string) Configuration::get('PS_PRODUCT_STOCK_MANAGEMENT', '1') !== '1'
        ) {
            return;
        }

        if ($product->isPack()) {
            $this->decrementPack($product, $quantity);

            return;
        }

        $this->decrementProduct($product, $quantity);
    }

    protected function decrementPack(Product $pack, float $quantity): void
    {
        $mode = (string) Configuration::get('PS_PACK_STOCK_TYPE', 'both');

        if (in_array($mode, ['pack', 'both'], true)) {
            $this->decrementProduct($pack, $quantity);
        }

        if (! in_array($mode, ['products', 'both'], true)) {
            return;
        }

        $pack->loadMissing('packItems.item');

        foreach ($pack->packItems as $packItem) {
            $component = $packItem->item;
            if (! $component) {
                continue;
            }

            $this->decrementProduct($component, $quantity * (float) $packItem->quantity);
        }
    }

    protected function decrementProduct(Product $product, float $quantity): void
    {
        if (! $product->track_inventory || $quantity <= 0) {
            return;
        }

        $product->decrement('quantity', $quantity);
    }

    protected function ensureProductAvailable(Product $product, float $quantity): void
    {
        if (! $product->track_inventory || (float) $product->quantity >= $quantity) {
            return;
        }

        throw ValidationException::withMessages([
            'quantity' => "{$product->name} has only ".number_format((float) $product->quantity, 2).' available.',
        ]);
    }
}

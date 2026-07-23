<?php

namespace Tests\Unit;

use App\Core\Configuration\Configuration;
use App\Core\Shipping\ShippingCalculator;
use App\Models\ShippingCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_price_range_handling_and_tax(): void
    {
        Configuration::updateValue('PS_SHIPPING_HANDLING', '2');
        $carrier = $this->carrier(['tax_rate' => 10]);
        $carrier->rateRanges()->create(['from_value' => 0, 'to_value' => 100, 'price' => 8]);

        $quote = app(ShippingCalculator::class)->quote($carrier->load('rateRanges'), 50, 1, 'US');

        $this->assertSame(10.0, $quote['cost']);
        $this->assertSame(1.0, $quote['tax']);
        $this->assertSame(11.0, $quote['total']);
    }

    public function test_it_rejects_country_and_weight_outside_carrier_limits(): void
    {
        $carrier = $this->carrier([
            'country_codes' => ['US'],
            'max_weight' => 5,
        ]);

        $calculator = app(ShippingCalculator::class);

        $this->assertNull($calculator->quote($carrier, 50, 1, 'IN'));
        $this->assertNull($calculator->quote($carrier, 50, 6, 'US'));
    }

    public function test_global_price_threshold_makes_shipping_free(): void
    {
        Configuration::updateValue('PS_SHIPPING_FREE_PRICE', '100');
        $carrier = $this->carrier(['price' => 12]);

        $quote = app(ShippingCalculator::class)->quote($carrier, 100, 1, 'US');

        $this->assertSame(0.0, $quote['total']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function carrier(array $attributes = []): ShippingCarrier
    {
        return ShippingCarrier::query()->create([
            'name' => 'Test carrier',
            'delay' => '2–3 days',
            'price' => 5,
            'currency' => 'USD',
            'billing_basis' => 'price',
            'apply_handling_cost' => true,
            'out_of_range_behavior' => 'disable',
            'active' => true,
            'position' => 1,
            ...$attributes,
        ]);
    }
}

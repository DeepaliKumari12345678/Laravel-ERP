<?php

namespace App\Core\Shipping;

use App\Core\Configuration\Configuration;
use App\Models\ShippingCarrier;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @param  array{width?: float, height?: float, depth?: float}  $dimensions
     * @return array{carrier: ShippingCarrier, cost: float, tax: float, total: float}|null
     */
    public function quote(
        ShippingCarrier $carrier,
        float $subtotal,
        float $weight,
        ?string $countryCode,
        array $dimensions = []
    ): ?array {
        if (! $carrier->active || ! $this->servesCountry($carrier, $countryCode)) {
            return null;
        }

        if (! $this->withinLimits($carrier, $weight, $dimensions)) {
            return null;
        }

        $value = $carrier->billing_basis === 'weight' ? $weight : $subtotal;
        $basePrice = $this->rangePrice($carrier, $value);
        if ($basePrice === null) {
            return null;
        }

        $freeFromPrice = (float) Configuration::get('PS_SHIPPING_FREE_PRICE', 0);
        $freeFromWeight = (float) Configuration::get('PS_SHIPPING_FREE_WEIGHT', 0);
        $free = $carrier->free_shipping
            || ($freeFromPrice > 0 && $subtotal >= $freeFromPrice)
            || ($freeFromWeight > 0 && $weight >= $freeFromWeight);

        $handling = $carrier->apply_handling_cost
            ? (float) Configuration::get('PS_SHIPPING_HANDLING', 0)
            : 0;

        $cost = $free ? 0.0 : round($basePrice + $handling, 2);
        $tax = round($cost * ((float) $carrier->tax_rate / 100), 2);

        return [
            'carrier' => $carrier,
            'cost' => $cost,
            'tax' => $tax,
            'total' => round($cost + $tax, 2),
        ];
    }

    /**
     * @param  Collection<int, ShippingCarrier>  $carriers
     * @return Collection<int, array{carrier: ShippingCarrier, cost: float, tax: float, total: float}>
     */
    public function available(
        Collection $carriers,
        float $subtotal,
        float $weight,
        ?string $countryCode,
        array $dimensions = []
    ): Collection {
        $quotes = $carriers
            ->map(fn (ShippingCarrier $carrier) => $this->quote(
                $carrier,
                $subtotal,
                $weight,
                $countryCode,
                $dimensions
            ))
            ->filter()
            ->values();

        return match ((string) Configuration::get('PS_SHIPPING_SORT_BY', 'position')) {
            'price' => $quotes->sortBy('total')->values(),
            'name' => $quotes->sortBy(fn (array $quote) => $quote['carrier']->name)->values(),
            default => $quotes->sortBy(fn (array $quote) => $quote['carrier']->position)->values(),
        };
    }

    protected function servesCountry(ShippingCarrier $carrier, ?string $countryCode): bool
    {
        $countries = array_filter($carrier->country_codes ?? []);

        return $countries === []
            || ($countryCode !== null && in_array(strtoupper($countryCode), $countries, true));
    }

    /**
     * @param  array{width?: float, height?: float, depth?: float}  $dimensions
     */
    protected function withinLimits(ShippingCarrier $carrier, float $weight, array $dimensions): bool
    {
        return ! (
            ((float) $carrier->max_weight > 0 && $weight > (float) $carrier->max_weight)
            || ((float) $carrier->max_width > 0 && ($dimensions['width'] ?? 0) > (float) $carrier->max_width)
            || ((float) $carrier->max_height > 0 && ($dimensions['height'] ?? 0) > (float) $carrier->max_height)
            || ((float) $carrier->max_depth > 0 && ($dimensions['depth'] ?? 0) > (float) $carrier->max_depth)
        );
    }

    protected function rangePrice(ShippingCarrier $carrier, float $value): ?float
    {
        $ranges = $carrier->relationLoaded('rateRanges')
            ? $carrier->rateRanges
            : $carrier->rateRanges()->get();

        if ($ranges->isEmpty()) {
            return (float) $carrier->price;
        }

        $range = $ranges->first(
            fn ($range) => $value >= (float) $range->from_value && $value <= (float) $range->to_value
        );

        if ($range) {
            return (float) $range->price;
        }

        if ($carrier->out_of_range_behavior === 'highest') {
            return (float) $ranges->sortByDesc('to_value')->first()->price;
        }

        return null;
    }
}

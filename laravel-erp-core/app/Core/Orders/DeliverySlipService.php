<?php

namespace App\Core\Orders;

use App\Core\Configuration\Configuration;
use App\Models\DeliverySlip;
use App\Models\Order;

/**
 * Delivery slip numbering (Orders > Delivery slips options).
 */
class DeliverySlipService
{
    public function createForOrder(
        Order $order,
        ?string $carrier = null,
        ?string $trackingNumber = null,
        string $status = 'prepared',
        ?string $notes = null,
    ): DeliverySlip {
        return DeliverySlip::query()->create([
            'number' => $this->allocateNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'carrier' => $carrier ?? $order->shipping_carrier_name,
            'tracking_number' => $trackingNumber,
            'status' => $status,
            'shipped_at' => now(),
            'notes' => $notes,
        ]);
    }

    public function allocateNumber(): string
    {
        $prefix = (string) Configuration::get('PS_DELIVERY_PREFIX', '#DF');
        $configured = (int) Configuration::get('PS_DELIVERY_NUMBER', 0);
        $seq = $configured > 0 ? $configured : max(1, (int) DeliverySlip::query()->count() + 1);
        $padded = str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

        Configuration::updateValue('PS_DELIVERY_NUMBER', (string) ($seq + 1));

        return $prefix.$padded;
    }

    public function productImageEnabled(): bool
    {
        return (string) Configuration::get('PS_PDF_IMG_DELIVERY', '0') === '1';
    }
}

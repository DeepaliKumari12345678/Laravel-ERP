<?php

namespace App\Core\Orders;

use App\Core\Configuration\Configuration;
use App\Models\CreditSlip;
use App\Models\Order;

/**
 * Credit slip numbering (Orders > Credit slips options).
 */
class CreditSlipService
{
    public function createForOrder(Order $order, float $amount, ?string $reason = null): CreditSlip
    {
        return CreditSlip::query()->create([
            'number' => $this->allocateNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $amount,
            'currency' => $order->currency,
            'reason' => $reason,
            'status' => 'issued',
            'issued_at' => now(),
        ]);
    }

    public function allocateNumber(): string
    {
        $prefix = (string) Configuration::get('PS_CREDIT_SLIP_PREFIX', '#CR');
        $configured = (int) Configuration::get('PS_CREDIT_SLIP_NUMBER', 0);
        $seq = $configured > 0 ? $configured : max(1, (int) CreditSlip::query()->count() + 1);
        $padded = str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

        Configuration::updateValue('PS_CREDIT_SLIP_NUMBER', (string) ($seq + 1));

        return $prefix.$padded;
    }
}

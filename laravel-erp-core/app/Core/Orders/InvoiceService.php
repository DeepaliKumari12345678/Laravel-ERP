<?php

namespace App\Core\Orders;

use App\Core\Configuration\Configuration;
use App\Models\Invoice;
use App\Models\Order;
use RuntimeException;

/**
 * Invoice creation using Orders > Invoices options.
 */
class InvoiceService
{
    public function enabled(): bool
    {
        return (string) Configuration::get('PS_INVOICE', '1') === '1';
    }

    public function createForOrder(Order $order, ?string $notes = null): Invoice
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Invoices are disabled in Invoice options.');
        }

        if ($order->invoices()->exists()) {
            throw new RuntimeException('An invoice already exists for this order.');
        }

        return Invoice::query()->create([
            'number' => $this->allocateNumber(),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'total' => $order->total,
            'currency' => $order->currency,
            'status' => 'issued',
            'issued_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Build next invoice number from PS_INVOICE_* settings, then bump the counter.
     */
    public function allocateNumber(): string
    {
        $prefix = (string) Configuration::get('PS_INVOICE_PREFIX', '#IN');
        $yearActive = (string) Configuration::get('PS_INVOICE_YEAR_ACTIVE', '0') === '1';
        $yearReset = (string) Configuration::get('PS_INVOICE_RESET', '0') === '1';
        $yearPos = Configuration::get('PS_INVOICE_YEAR_POS', 'after');
        $configured = (int) Configuration::get('PS_INVOICE_NUMBER', 0);
        $year = now()->format('Y');

        $seq = $configured > 0 ? $configured : $this->guessNextSequence();

        if ($yearReset && $yearActive && $this->shouldResetForNewYear()) {
            $seq = $configured > 0 ? $configured : 1;
        }

        $padded = str_pad((string) $seq, 5, '0', STR_PAD_LEFT);

        if ($yearActive) {
            $number = $yearPos === 'before'
                ? $year.$prefix.$padded
                : $prefix.$padded.$year;
        } else {
            $number = $prefix.$padded;
        }

        Configuration::updateValue('PS_INVOICE_NUMBER', (string) ($seq + 1));

        return $number;
    }

    protected function guessNextSequence(): int
    {
        return max(1, (int) Invoice::query()->count() + 1);
    }

    protected function shouldResetForNewYear(): bool
    {
        $last = Invoice::query()->latest('id')->first();

        if (! $last?->issued_at) {
            return false;
        }

        return (int) $last->issued_at->format('Y') !== (int) now()->format('Y');
    }
}

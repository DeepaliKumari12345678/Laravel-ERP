<?php

namespace App\Http\Controllers\Admin;

use App\Core\Configuration\Configuration;
use App\Http\Controllers\Controller;
use App\Models\CreditSlip;
use App\Models\DeliverySlip;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderDocumentController extends Controller
{
    public function showInvoice(Invoice $invoice): View
    {
        return $this->showDocument($invoice, 'invoice');
    }

    public function downloadInvoice(Invoice $invoice): Response
    {
        return $this->downloadDocument($invoice, 'invoice');
    }

    public function showCreditSlip(CreditSlip $creditSlip): View
    {
        return $this->showDocument($creditSlip, 'credit-slip');
    }

    public function downloadCreditSlip(CreditSlip $creditSlip): Response
    {
        return $this->downloadDocument($creditSlip, 'credit-slip');
    }

    public function showDeliverySlip(DeliverySlip $deliverySlip): View
    {
        return $this->showDocument($deliverySlip, 'delivery-slip');
    }

    public function downloadDeliverySlip(DeliverySlip $deliverySlip): Response
    {
        return $this->downloadDocument($deliverySlip, 'delivery-slip');
    }

    protected function showDocument(Model $document, string $kind): View
    {
        return view('admin.orders.document', $this->documentData($document, $kind));
    }

    protected function downloadDocument(Model $document, string $kind): Response
    {
        $data = $this->documentData($document, $kind, forPdf: true);
        $pdf = Pdf::loadView('admin.orders.document-pdf', $data)->setPaper('a4');

        return $pdf->download($document->getAttribute('number').'.pdf');
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(Model $document, string $kind, bool $forPdf = false): array
    {
        $document->load(['order.items.product', 'customer']);
        $order = $document->getRelation('order');

        $showProductImage = match ($kind) {
            'delivery-slip' => (string) Configuration::get('PS_PDF_IMG_DELIVERY', '0') === '1',
            'invoice' => (string) Configuration::get('PS_INVOICE_PRODUCT_IMAGE', '0') === '1',
            default => false,
        };

        $definition = match ($kind) {
            'invoice' => [
                'title' => 'Invoice',
                'date' => $document->getAttribute('issued_at'),
                'amount' => $document->getAttribute('total'),
                'currency' => $document->getAttribute('currency'),
                'notes' => $document->getAttribute('notes'),
                'indexRoute' => 'admin.invoices.index',
                'downloadRoute' => 'admin.invoices.download',
            ],
            'credit-slip' => [
                'title' => 'Credit slip',
                'date' => $document->getAttribute('issued_at'),
                'amount' => $document->getAttribute('amount'),
                'currency' => $document->getAttribute('currency'),
                'notes' => $document->getAttribute('reason'),
                'indexRoute' => 'admin.credit-slips.index',
                'downloadRoute' => 'admin.credit-slips.download',
            ],
            'delivery-slip' => [
                'title' => 'Delivery slip',
                'date' => $document->getAttribute('shipped_at') ?? $document->getAttribute('created_at'),
                'amount' => null,
                'currency' => $order?->currency,
                'notes' => $document->getAttribute('notes'),
                'indexRoute' => 'admin.delivery-slips.index',
                'downloadRoute' => 'admin.delivery-slips.download',
            ],
        };

        return [
            'document' => $document,
            'kind' => $kind,
            'definition' => $definition,
            'order' => $order,
            'customer' => $document->getRelation('customer'),
            'showProductImage' => $showProductImage,
            'forPdf' => $forPdf,
            'shop' => [
                'name' => Configuration::get('PS_SHOP_NAME', config('app.name')),
                'email' => Configuration::get('PS_SHOP_EMAIL', ''),
                'phone' => Configuration::get('PS_SHOP_PHONE', ''),
                'address' => Configuration::get('PS_SHOP_ADDRESS', ''),
                'postcode' => Configuration::get('PS_SHOP_POSTCODE', ''),
                'city' => Configuration::get('PS_SHOP_CITY', ''),
                'state' => Configuration::get('PS_SHOP_STATE', ''),
                'country' => Configuration::get('PS_SHOP_COUNTRY', ''),
            ],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class QuotationConversionService
{
    public function __construct(
        private InvoiceCreationService $invoiceCreationService
    ) {}

    /**
     * Convert an accepted quotation into an invoice atomically.
     *
     * @param Quotation $quotation
     * @return Invoice
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function convertToInvoice(Quotation $quotation): Invoice
    {
        return DB::transaction(function () use ($quotation) {
            // 1. Pessimistic row locking on quotation
            $lockedQuotation = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedQuotation) {
                throw new InvalidArgumentException("Quotation not found.");
            }

            // 2. Enforce accepted-only conversion eligibility
            if ($lockedQuotation->status !== 'accepted') {
                throw new InvalidArgumentException(
                    "Only accepted quotations can be converted to an invoice. Current status: {$lockedQuotation->status}."
                );
            }

            // 3. Prevent duplicate conversion
            if (Invoice::where('quotation_id', $lockedQuotation->id)->exists()) {
                throw new InvalidArgumentException(
                    "Quotation {$lockedQuotation->quotation_number} has already been converted to an invoice."
                );
            }

            // 4. Protect against appointment collision if appointment-linked
            if ($lockedQuotation->appointment_id && Invoice::where('appointment_id', $lockedQuotation->appointment_id)->exists()) {
                throw new InvalidArgumentException(
                    "An invoice already exists for the appointment linked to quotation {$lockedQuotation->quotation_number}."
                );
            }

            // 5. Load and validate items
            $lockedQuotation->loadMissing(['items' => fn($q) => $q->orderBy('sort_order')->orderBy('id')]);

            if ($lockedQuotation->items->isEmpty()) {
                throw new InvalidArgumentException("Cannot convert a quotation without items.");
            }

            // 6. Map quotation item historical snapshots to invoice items
            $invoiceItems = $lockedQuotation->items->map(function ($item) {
                return [
                    'service_id'        => $item->service_id,
                    'staff_id'          => $item->staff_id,
                    'quotation_item_id' => $item->id,
                    'description'       => $item->description,
                    'quantity'          => (float) $item->quantity,
                    'unit_price'        => (float) $item->unit_price,
                    'discount_amount'   => (float) $item->discount_amount,
                    'tax_rate'          => (float) $item->tax_rate,
                    'sort_order'        => (int) $item->sort_order,
                ];
            })->all();

            // 7. Delegate invoice persistence to InvoiceCreationService
            $invoice = $this->invoiceCreationService->createInvoice([
                'quotation_id'   => $lockedQuotation->id,
                'appointment_id' => $lockedQuotation->appointment_id,
                'client_id'      => $lockedQuotation->client_id,
                'staff_id'       => $lockedQuotation->staff_id,
                'issued_date'    => now()->toDateString(),
                'due_date'       => now()->addDays(14)->toDateString(),
                'status'         => 'outstanding',
                'items'          => $invoiceItems,
            ]);

            // 8. Update quotation status to converted atomically
            $lockedQuotation->status = 'converted';
            $lockedQuotation->save();

            return $invoice;
        });
    }
}

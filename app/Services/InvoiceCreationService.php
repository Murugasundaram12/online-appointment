<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InvoiceCreationService
{
    public function __construct(
        private InvoiceCalculationService $calculator
    ) {}

    /**
     * Create an invoice with normalized line items and server-calculated totals.
     *
     * @param array $data
     * @return Invoice
     * @throws InvalidArgumentException|RuntimeException
     */
    public function createInvoice(array $data): Invoice
    {
        $clientId = $data['client_id'] ?? null;
        $staffId = $data['staff_id'] ?? null;
        $appointmentId = !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null;

        if (!$clientId) {
            throw new InvalidArgumentException('Client ID is required.');
        }

        if (!$staffId) {
            throw new InvalidArgumentException('Staff ID is required.');
        }

        $rawItems = $this->extractRawItems($data, $appointmentId, (int) $staffId);

        if (empty($rawItems)) {
            throw new InvalidArgumentException('Cannot create invoice without at least one invoice item.');
        }

        // Normalize item order and attributes
        $normalizedItems = $this->normalizeItems($rawItems, (int) $staffId);

        // Server-side calculation via InvoiceCalculationService
        $calculation = $this->calculator->calculateInvoice($normalizedItems);

        return DB::transaction(function () use ($data, $appointmentId, $clientId, $staffId, $calculation) {
            $appointment = null;
            if ($appointmentId) {
                $appointment = Appointment::where('id', $appointmentId)->lockForUpdate()->first();
                if (!$appointment) {
                    throw new InvalidArgumentException("Appointment #{$appointmentId} not found.");
                }

                if ($appointment->status === 'cancelled') {
                    throw new InvalidArgumentException("Cannot create an invoice for a cancelled appointment.");
                }

                if (Invoice::where('appointment_id', $appointmentId)->exists()) {
                    throw new InvalidArgumentException("An invoice already exists for the selected appointment.");
                }
            }

            $invoiceNumber = !empty($data['invoice_number']) ? (string) $data['invoice_number'] : $this->generateInvoiceNumber();
            $calculatedTotal = $calculation['total_amount'];

            $initialPaid = min(
                max(0.0, (float) ($data['paid_amount'] ?? ($data['initial_paid'] ?? 0))),
                $calculatedTotal
            );

            $status = $data['status'] ?? null;
            if ($status === 'void') {
                $initialStatus = 'void';
            } else {
                $initialStatus = 'outstanding';
            }

            $invoice = Invoice::create([
                'appointment_id'  => $appointmentId,
                'quotation_id'    => $data['quotation_id'] ?? null,
                'client_id'       => (int) $clientId,
                'staff_id'        => (int) $staffId,
                'invoice_number'  => $invoiceNumber,
                'subtotal'        => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'tax_amount'      => $calculation['tax_amount'],
                'total_amount'    => $calculatedTotal,
                'paid_amount'     => 0,
                'status'          => $initialStatus,
                'issued_date'     => $data['issued_date'] ?? now()->toDateString(),
                'due_date'        => $data['due_date'] ?? null,
            ]);

            foreach ($calculation['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'        => $invoice->id,
                    'service_id'        => $item['service_id'] ?? null,
                    'staff_id'          => $item['staff_id'] ?? null,
                    'quotation_item_id' => $item['quotation_item_id'] ?? null,
                    'description'       => $item['description'] ?? 'Invoice Item',
                    'quantity'          => $item['quantity'],
                    'unit_price'        => $item['unit_price'],
                    'discount_amount'   => $item['discount_amount'],
                    'tax_rate'          => $item['tax_rate'],
                    'tax_amount'        => $item['tax_amount'],
                    'line_total'        => $item['line_total'],
                    'sort_order'        => $item['sort_order'],
                ]);
            }

            // Consistency checks inside transaction
            if ($invoice->items()->count() < 1) {
                throw new RuntimeException("Failed to create invoice items for invoice #{$invoice->id}");
            }

            $itemsTotal = (float) $invoice->items()->sum('line_total');
            if (round(abs($itemsTotal - (float) $invoice->total_amount), 2) !== 0.00) {
                throw new RuntimeException("Invoice line totals ({$itemsTotal}) do not match total amount ({$invoice->total_amount})");
            }

            $itemsSubtotal = (float) $invoice->items()->sum(DB::raw('quantity * unit_price'));
            if (round(abs($itemsSubtotal - (float) $invoice->subtotal), 2) !== 0.00) {
                throw new RuntimeException("Invoice subtotal ({$itemsSubtotal}) do not match invoice subtotal ({$invoice->subtotal})");
            }

            $itemsDiscount = (float) $invoice->items()->sum('discount_amount');
            if (round(abs($itemsDiscount - (float) $invoice->discount_amount), 2) !== 0.00) {
                throw new RuntimeException("Invoice discount ({$itemsDiscount}) does not match invoice discount amount ({$invoice->discount_amount})");
            }

            $itemsTax = (float) $invoice->items()->sum('tax_amount');
            if (round(abs($itemsTax - (float) $invoice->tax_amount), 2) !== 0.00) {
                throw new RuntimeException("Invoice tax ({$itemsTax}) does not match invoice tax amount ({$invoice->tax_amount})");
            }

            if ($initialPaid > 0 && $invoice->status !== 'void') {
                PaymentRecord::create([
                    'invoice_id'     => $invoice->id,
                    'amount'         => $initialPaid,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date'   => $data['payment_date'] ?? ($data['issued_date'] ?? now()->toDateString()),
                    'transaction_id' => 'INIT-' . $invoice->id,
                ]);

                $invoice->paid_amount = $initialPaid;
                $invoice->status = $this->statusForAmounts($initialPaid, $calculatedTotal);
                $invoice->save();
            }

            return $invoice->load(['items', 'payments', 'client', 'staff']);
        });
    }

    /**
     * Auto-create invoice from a completed appointment (idempotent).
     *
     * @param Appointment $appointment
     * @return Invoice|null
     */
    public function createFromAppointment(Appointment $appointment): ?Invoice
    {
        if ($appointment->status !== 'completed' || !$appointment->client_id || !$appointment->staff_id) {
            return null;
        }

        $appointment->loadMissing(['service', 'client', 'staff', 'location']);

        if (!$appointment->service) {
            return null;
        }

        $unitPrice = (float) ($appointment->service->price ?? 0);
        if ($unitPrice < 0) {
            return null;
        }

        if (Invoice::where('appointment_id', $appointment->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($appointment, $unitPrice) {
            $lockedAppt = Appointment::where('id', $appointment->id)->lockForUpdate()->first();
            if (!$lockedAppt) {
                return null;
            }

            if (Invoice::where('appointment_id', $appointment->id)->exists()) {
                return null;
            }

            $items = [
                [
                    'service_id'      => $appointment->service_id,
                    'staff_id'        => $appointment->staff_id,
                    'description'     => $appointment->service->name,
                    'quantity'        => 1.00,
                    'unit_price'      => $unitPrice,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                    'sort_order'      => 1,
                ]
            ];

            return $this->createInvoice([
                'appointment_id' => $appointment->id,
                'client_id'      => $appointment->client_id,
                'staff_id'       => $appointment->staff_id,
                'issued_date'    => now()->toDateString(),
                'due_date'       => now()->addDays(14)->toDateString(),
                'status'         => 'outstanding',
                'items'          => $items,
            ]);
        });
    }

    /**
     * Generate the next unique invoice number.
     *
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = BusinessSetting::where('key', 'invoice_prefix')->value('value') ?: 'INV';
        $maxId = (Invoice::max('id') ?? 0) + 1;
        return $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $maxId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Derive invoice payment status from paid and total amounts.
     *
     * @param float $paid
     * @param float $total
     * @return string
     */
    public function statusForAmounts(float $paid, float $total): string
    {
        if ($paid >= $total) {
            return 'paid';
        }
        return $paid > 0 ? 'partially_paid' : 'outstanding';
    }

    /**
     * Extract raw item arrays from data or appointment fallback.
     *
     * @param array $data
     * @param int|null $appointmentId
     * @param int $staffId
     * @return array
     */
    private function extractRawItems(array $data, ?int $appointmentId, int $staffId): array
    {
        if (!empty($data['items']) && is_array($data['items']) && count($data['items']) > 0) {
            return $data['items'];
        }

        if ($appointmentId) {
            $appointment = Appointment::with('service')->find($appointmentId);
            if ($appointment && $appointment->service) {
                return [
                    [
                        'service_id'      => $appointment->service->id,
                        'staff_id'        => $staffId,
                        'description'     => $appointment->service->name,
                        'quantity'        => 1.00,
                        'unit_price'      => (float) ($appointment->service->price ?? 0.00),
                        'discount_amount' => 0.00,
                        'tax_rate'        => 0.00,
                        'sort_order'      => 1,
                    ]
                ];
            }
        }

        return [];
    }

    /**
     * Normalize items with deterministic sort order, snapshot service price if needed, and set defaults.
     *
     * @param array $items
     * @param int $defaultStaffId
     * @return array
     */
    private function normalizeItems(array $items, int $defaultStaffId): array
    {
        $normalized = [];
        $order = 1;

        foreach ($items as $item) {
            $serviceId = !empty($item['service_id']) ? (int) $item['service_id'] : null;
            $service = $serviceId ? Service::find($serviceId) : null;

            $description = !empty($item['description'])
                ? trim((string) $item['description'])
                : ($service?->name ?? 'Invoice Item');

            $unitPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) ($service?->price ?? 0.00);

            $quantity = array_key_exists('quantity', $item) && $item['quantity'] !== null
                ? (float) $item['quantity']
                : 1.00;

            $discountAmount = array_key_exists('discount_amount', $item) && $item['discount_amount'] !== null
                ? (float) $item['discount_amount']
                : 0.00;

            $taxRate = array_key_exists('tax_rate', $item) && $item['tax_rate'] !== null
                ? (float) $item['tax_rate']
                : 0.00;

            $normalized[] = [
                'service_id'        => $serviceId,
                'staff_id'          => !empty($item['staff_id']) ? (int) $item['staff_id'] : $defaultStaffId,
                'quotation_item_id' => !empty($item['quotation_item_id']) ? (int) $item['quotation_item_id'] : null,
                'description'       => $description,
                'quantity'          => $quantity,
                'unit_price'        => $unitPrice,
                'discount_amount'   => $discountAmount,
                'tax_rate'          => $taxRate,
                'sort_order'        => $order++,
            ];
        }

        return $normalized;
    }
}

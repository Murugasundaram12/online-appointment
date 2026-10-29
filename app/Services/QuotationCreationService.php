<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class QuotationCreationService
{
    private InvoiceCreationService $invoiceCreationService;

    public function __construct(
        private InvoiceCalculationService $calculationService,
        ?InvoiceCreationService $invoiceCreationService = null
    ) {
        $this->invoiceCreationService = $invoiceCreationService ?? app(InvoiceCreationService::class);
    }

    /**
     * Create a quotation and its line items inside an atomic database transaction.
     *
     * @param array $data
     * @return Quotation
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createQuotation(array $data): Quotation
    {
        $clientId = $data['client_id'] ?? null;
        if (!$clientId || !Client::where('id', $clientId)->exists()) {
            throw new InvalidArgumentException('A valid client_id is required.');
        }

        $staffId = $data['staff_id'] ?? null;
        if (!$staffId || !Staff::where('id', $staffId)->exists()) {
            throw new InvalidArgumentException('A valid staff_id is required.');
        }

        $appointmentId = !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null;
        if ($appointmentId) {
            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                throw new InvalidArgumentException('The selected appointment does not exist.');
            }
            if ((int) $appointment->client_id !== (int) $clientId) {
                throw new InvalidArgumentException('The selected appointment does not belong to the selected client.');
            }
        }

        $rawItems = $data['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) < 1) {
            throw new InvalidArgumentException('At least one quotation item is required.');
        }

        foreach ($rawItems as $index => $item) {
            if (!empty($item['staff_id']) && (int) $item['staff_id'] !== (int) $staffId) {
                throw new InvalidArgumentException(
                    "All quotation items must belong to the selected practitioner. Item #" . ($index + 1) . " has a mismatched practitioner."
                );
            }
        }

        $normalizedItems = $this->normalizeItems($rawItems, (int) $staffId);

        // Server-side calculation via InvoiceCalculationService
        $calculation = $this->calculationService->calculateInvoice($normalizedItems);

        return DB::transaction(function () use ($data, $clientId, $staffId, $appointmentId, $calculation) {
            // Synchronize on linked appointment row to prevent race with auto-invoicing
            if ($appointmentId) {
                $lockedAppointment = Appointment::where('id', $appointmentId)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedAppointment) {
                    throw new InvalidArgumentException('The selected appointment does not exist.');
                }

                if ((int) $lockedAppointment->client_id !== (int) $clientId) {
                    throw new InvalidArgumentException('The selected appointment does not belong to the selected client.');
                }
            }

            $quotationNumber = !empty($data['quotation_number'])
                ? (string) $data['quotation_number']
                : $this->generateQuotationNumber();

            $status = !empty($data['status']) ? (string) $data['status'] : 'draft';

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'client_id'        => (int) $clientId,
                'staff_id'         => (int) $staffId,
                'created_by'       => !empty($data['created_by']) ? (int) $data['created_by'] : null,
                'appointment_id'   => $appointmentId,
                'status'           => $status,
                'issued_date'      => $data['issued_date'] ?? now()->toDateString(),
                'valid_until'      => $data['valid_until'] ?? now()->addDays(30)->toDateString(),
                'subtotal'         => $calculation['subtotal'],
                'discount_amount'  => $calculation['discount_amount'],
                'tax_amount'       => $calculation['tax_amount'],
                'total_amount'     => $calculation['total_amount'],
                'notes'            => $data['notes'] ?? null,
                'terms'            => $data['terms'] ?? null,
            ]);

            foreach ($calculation['items'] as $item) {
                QuotationItem::create([
                    'quotation_id'    => $quotation->id,
                    'service_id'      => $item['service_id'] ?? null,
                    'staff_id'        => $item['staff_id'] ?? null,
                    'description'     => $item['description'] ?? 'Quotation Item',
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'tax_rate'        => $item['tax_rate'],
                    'tax_amount'      => $item['tax_amount'],
                    'line_total'      => $item['line_total'],
                    'sort_order'      => $item['sort_order'],
                ]);
            }

            // Consistency checks inside transaction
            if ($quotation->items()->count() < 1) {
                throw new RuntimeException("Failed to create quotation items for quotation #{$quotation->id}");
            }

            $itemsTotal = (float) $quotation->items()->sum('line_total');
            if (round(abs($itemsTotal - (float) $quotation->total_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation line totals ({$itemsTotal}) do not match total amount ({$quotation->total_amount})");
            }

            $itemsSubtotal = (float) $quotation->items()->sum(DB::raw('quantity * unit_price'));
            if (round(abs($itemsSubtotal - (float) $quotation->subtotal), 2) !== 0.00) {
                throw new RuntimeException("Quotation item subtotal ({$itemsSubtotal}) does not match quotation subtotal ({$quotation->subtotal})");
            }

            $itemsDiscount = (float) $quotation->items()->sum('discount_amount');
            if (round(abs($itemsDiscount - (float) $quotation->discount_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation item discount ({$itemsDiscount}) does not match quotation discount amount ({$quotation->discount_amount})");
            }

            $itemsTax = (float) $quotation->items()->sum('tax_amount');
            if (round(abs($itemsTax - (float) $quotation->tax_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation item tax ({$itemsTax}) does not match quotation tax amount ({$quotation->tax_amount})");
            }

            return $quotation;
        });
    }

    /**
     * Update an existing draft quotation and its line items inside an atomic database transaction.
     *
     * @param Quotation $quotation
     * @param array $data
     * @return Quotation
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function updateDraftQuotation(Quotation $quotation, array $data): Quotation
    {
        $clientId = $data['client_id'] ?? null;
        if (!$clientId || !Client::where('id', $clientId)->exists()) {
            throw new InvalidArgumentException('A valid client_id is required.');
        }

        $staffId = $data['staff_id'] ?? null;
        if (!$staffId || !Staff::where('id', $staffId)->exists()) {
            throw new InvalidArgumentException('A valid staff_id is required.');
        }

        $appointmentId = !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null;
        if ($appointmentId) {
            $appointment = Appointment::find($appointmentId);
            if (!$appointment) {
                throw new InvalidArgumentException('The selected appointment does not exist.');
            }
            if ((int) $appointment->client_id !== (int) $clientId) {
                throw new InvalidArgumentException('The selected appointment does not belong to the selected client.');
            }
        }

        $rawItems = $data['items'] ?? [];
        if (!is_array($rawItems) || count($rawItems) < 1) {
            throw new InvalidArgumentException('At least one quotation item is required.');
        }

        foreach ($rawItems as $index => $item) {
            if (!empty($item['staff_id']) && (int) $item['staff_id'] !== (int) $staffId) {
                throw new InvalidArgumentException(
                    "All quotation items must belong to the selected practitioner. Item #" . ($index + 1) . " has a mismatched practitioner."
                );
            }
        }

        $normalizedItems = $this->normalizeItems($rawItems, (int) $staffId);

        // Server-side calculation via InvoiceCalculationService
        $calculation = $this->calculationService->calculateInvoice($normalizedItems);

        return DB::transaction(function () use ($quotation, $data, $clientId, $staffId, $appointmentId, $calculation) {
            // Pessimistic row locking on quotation
            $lockedQuotation = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Strict status check: only draft status can be edited
            if ($lockedQuotation->status !== 'draft') {
                throw new InvalidArgumentException(
                    "Only draft quotations can be edited. Current status: {$lockedQuotation->status}."
                );
            }

            // Safety check: reject edit if quotation already has linked invoices
            if (Invoice::where('quotation_id', $lockedQuotation->id)->exists()) {
                throw new InvalidArgumentException(
                    "Quotation {$lockedQuotation->quotation_number} cannot be edited because it is linked to an existing invoice."
                );
            }

            $oldAppointmentId = $lockedQuotation->appointment_id ? (int) $lockedQuotation->appointment_id : null;
            $targetAppointmentId = $appointmentId ? (int) $appointmentId : null;

            // Collect distinct non-null appointment IDs and lock in ascending primary-key order
            $idsToLock = array_values(array_filter(array_unique([$oldAppointmentId, $targetAppointmentId])));
            sort($idsToLock);

            $lockedAppointments = [];
            foreach ($idsToLock as $id) {
                $lockedAppointments[$id] = Appointment::where('id', $id)->lockForUpdate()->first();
            }

            // Verify target appointment if present
            if ($targetAppointmentId) {
                $targetAppointment = $lockedAppointments[$targetAppointmentId] ?? null;
                if (!$targetAppointment) {
                    throw new InvalidArgumentException('The selected appointment does not exist.');
                }
                if ((int) $targetAppointment->client_id !== (int) $clientId) {
                    throw new InvalidArgumentException('The selected appointment does not belong to the selected client.');
                }
            }

            // Update parent quotation attributes (preserving quotation_number, created_by, status)
            $lockedQuotation->update([
                'client_id'       => (int) $clientId,
                'staff_id'        => (int) $staffId,
                'appointment_id'  => $targetAppointmentId,
                'issued_date'     => $data['issued_date'] ?? $lockedQuotation->issued_date,
                'valid_until'     => $data['valid_until'] ?? $lockedQuotation->valid_until,
                'subtotal'        => $calculation['subtotal'],
                'discount_amount' => $calculation['discount_amount'],
                'tax_amount'      => $calculation['tax_amount'],
                'total_amount'    => $calculation['total_amount'],
                'notes'           => $data['notes'] ?? null,
                'terms'           => $data['terms'] ?? null,
            ]);

            // Replace line items
            $lockedQuotation->items()->delete();

            foreach ($calculation['items'] as $item) {
                QuotationItem::create([
                    'quotation_id'    => $lockedQuotation->id,
                    'service_id'      => $item['service_id'] ?? null,
                    'staff_id'        => $item['staff_id'] ?? null,
                    'description'     => $item['description'] ?? 'Quotation Item',
                    'quantity'        => $item['quantity'],
                    'unit_price'      => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'tax_rate'        => $item['tax_rate'],
                    'tax_amount'      => $item['tax_amount'],
                    'line_total'      => $item['line_total'],
                    'sort_order'      => $item['sort_order'],
                ]);
            }

            // Consistency checks inside transaction
            if ($lockedQuotation->items()->count() < 1) {
                throw new RuntimeException("Failed to create quotation items for quotation #{$lockedQuotation->id}");
            }

            $itemsTotal = (float) $lockedQuotation->items()->sum('line_total');
            if (round(abs($itemsTotal - (float) $lockedQuotation->total_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation line totals ({$itemsTotal}) do not match total amount ({$lockedQuotation->total_amount})");
            }

            $itemsSubtotal = (float) $lockedQuotation->items()->sum(DB::raw('quantity * unit_price'));
            if (round(abs($itemsSubtotal - (float) $lockedQuotation->subtotal), 2) !== 0.00) {
                throw new RuntimeException("Quotation item subtotal ({$itemsSubtotal}) does not match quotation subtotal ({$lockedQuotation->subtotal})");
            }

            $itemsDiscount = (float) $lockedQuotation->items()->sum('discount_amount');
            if (round(abs($itemsDiscount - (float) $lockedQuotation->discount_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation item discount ({$itemsDiscount}) does not match quotation discount amount ({$lockedQuotation->discount_amount})");
            }

            $itemsTax = (float) $lockedQuotation->items()->sum('tax_amount');
            if (round(abs($itemsTax - (float) $lockedQuotation->tax_amount), 2) !== 0.00) {
                throw new RuntimeException("Quotation item tax ({$itemsTax}) does not match quotation tax amount ({$lockedQuotation->tax_amount})");
            }

            // If the quotation was unlinked or moved away from an old appointment,
            // check if the old appointment is completed and now has neither an invoice nor an active quotation.
            if ($oldAppointmentId && $oldAppointmentId !== $targetAppointmentId) {
                $oldAppt = $lockedAppointments[$oldAppointmentId] ?? null;
                if ($oldAppt && $oldAppt->status === 'completed' && !Invoice::where('appointment_id', $oldAppt->id)->exists()) {
                    $hasOtherActiveQuote = Quotation::where('appointment_id', $oldAppt->id)
                        ->where('id', '!=', $lockedQuotation->id)
                        ->whereIn('status', ['draft', 'sent', 'accepted'])
                        ->exists();

                    if (!$hasOtherActiveQuote) {
                        $this->invoiceCreationService->createFromAppointment($oldAppt);
                    }
                }
            }

            return $lockedQuotation->fresh(['items', 'client', 'staff', 'appointment']);
        });
    }

    /**
     * Approve/accept a draft or sent quotation.
     *
     * @param Quotation $quotation
     * @return Quotation
     * @throws InvalidArgumentException
     */
    public function acceptQuotation(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            $lockedQuotation = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedQuotation->status, ['draft', 'sent'], true)) {
                throw new InvalidArgumentException(
                    "Quotation {$lockedQuotation->quotation_number} cannot be approved because it is in '{$lockedQuotation->status}' status."
                );
            }

            $lockedQuotation->status = 'accepted';
            $lockedQuotation->save();

            return $lockedQuotation->fresh(['items', 'client', 'staff', 'appointment']);
        });
    }

    /**
     * Cancel/reject a quotation.
     * Allowed only from 'draft', 'sent', or 'accepted' provided no linked invoice exists.
     *
     * @param Quotation $quotation
     * @return Quotation
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function cancelQuotation(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation) {
            $lockedQuotation = Quotation::where('id', $quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedQuotation->status, ['draft', 'sent', 'accepted'], true)) {
                throw new InvalidArgumentException(
                    "Quotation {$lockedQuotation->quotation_number} cannot be cancelled because it is in '{$lockedQuotation->status}' status."
                );
            }

            if (Invoice::where('quotation_id', $lockedQuotation->id)->exists()) {
                throw new RuntimeException(
                    "Quotation {$lockedQuotation->quotation_number} cannot be cancelled because an invoice has already been created for it."
                );
            }

            $lockedQuotation->status = 'rejected';
            $lockedQuotation->save();

            // If the linked appointment was completed while awaiting quotation conversion,
            // generate the deferred appointment invoice now that the quotation is cancelled.
            if ($lockedQuotation->appointment_id) {
                $linkedAppointment = Appointment::where('id', $lockedQuotation->appointment_id)->first();
                if ($linkedAppointment && $linkedAppointment->status === 'completed' && !Invoice::where('appointment_id', $linkedAppointment->id)->exists()) {
                    $this->invoiceCreationService->createFromAppointment($linkedAppointment);
                }
            }

            return $lockedQuotation->fresh(['items', 'client', 'staff', 'appointment']);
        });
    }

    /**
     * Generate the next unique quotation number.
     *
     * @return string
     */
    public function generateQuotationNumber(): string
    {
        $prefix = BusinessSetting::where('key', 'quotation_prefix')->value('value') ?: 'QUO';
        $maxId = (Quotation::max('id') ?? 0) + 1;
        $candidate = $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $maxId, 4, '0', STR_PAD_LEFT);

        while (Quotation::where('quotation_number', $candidate)->exists()) {
            $maxId++;
            $candidate = $prefix . '-' . now()->format('Ymd') . '-' . str_pad((string) $maxId, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
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

            if (array_key_exists('unit_price', $item) && $item['unit_price'] !== null && $item['unit_price'] !== '') {
                $unitPrice = (float) $item['unit_price'];
            } elseif ($service) {
                $unitPrice = (float) ($service->price ?? 0.00);
            } else {
                $unitPrice = 0.00;
            }

            $description = !empty($item['description'])
                ? trim((string) $item['description'])
                : ($service?->name ?? 'Quotation Item');

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
                'service_id'      => $serviceId,
                'staff_id'        => $defaultStaffId,
                'description'     => $description,
                'quantity'        => $quantity,
                'unit_price'      => $unitPrice,
                'discount_amount' => $discountAmount,
                'tax_rate'        => $taxRate,
                'sort_order'      => $order++,
            ];
        }

        return $normalized;
    }
}

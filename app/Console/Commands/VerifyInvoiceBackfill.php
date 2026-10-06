<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Services\InvoiceCalculationService;
use Illuminate\Console\Command;

class VerifyInvoiceBackfill extends Command
{
    protected $signature = 'invoices:verify-backfill';

    protected $description = 'Perform a read-only verification of database state and backfill readiness';

    public function handle(InvoiceCalculationService $calculator): int
    {
        $this->line('');
        $this->line('==================================================');
        $this->line('STEP 3 — BACKFILL READ-ONLY VERIFICATION');
        $this->line('==================================================');

        $currency = BusinessSetting::where('key', 'currency')->value('value') ?: '$';

        $originalAudit = [
            'total_invoices'      => 8,
            'total_items'         => 0,
            'eligible'            => 8,
            'appointment_linked'  => 8,
            'missing_appointment' => 0,
            'missing_service'     => 0,
            'financial_anomalies' => 0,
            'total_amount'        => 718.50,
            'paid_amount'         => 476.80,
        ];

        $originalSnapshot = [
            66 => ['num' => 'INV-20260819-0001', 'total' => 101.70, 'paid' => 101.70, 'status' => 'paid', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            67 => ['num' => 'INV-20260902-0067', 'total' => 101.70, 'paid' => 101.70, 'status' => 'paid', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            68 => ['num' => 'INV-20260911-0068', 'total' => 101.70, 'paid' => 101.70, 'status' => 'paid', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            69 => ['num' => 'INV-20260911-0069', 'total' => 101.70, 'paid' => 0.00, 'status' => 'outstanding', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            70 => ['num' => 'INV-20260915-0070', 'total' => 70.00, 'paid' => 70.00, 'status' => 'paid', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            71 => ['num' => 'INV-20260922-0071', 'total' => 101.70, 'paid' => 101.70, 'status' => 'paid', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            72 => ['num' => 'INV-20261005-0072', 'total' => 70.00, 'paid' => 0.00, 'status' => 'outstanding', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
            73 => ['num' => 'INV-20261005-0073', 'total' => 70.00, 'paid' => 0.00, 'status' => 'outstanding', 'subtotal' => null, 'discount' => 0.00, 'tax' => 0.00],
        ];

        // 1. Current Database State
        $currentTotalInvoices = Invoice::count();
        $currentTotalInvoiceItems = InvoiceItem::count();
        $invoicesWithItems = Invoice::has('items')->count();
        $invoicesWithoutItems = Invoice::doesntHave('items')->count();

        $this->info('--- VERIFICATION 1: CURRENT DATABASE STATE ---');
        $this->line(sprintf('%-35s : %d', 'Total invoices', $currentTotalInvoices));
        $this->line(sprintf('%-35s : %d', 'Total invoice_items', $currentTotalInvoiceItems));
        $this->line(sprintf('%-35s : %d', 'Invoices with invoice_items', $invoicesWithItems));
        $this->line(sprintf('%-35s : %d', 'Invoices without invoice_items', $invoicesWithoutItems));
        $this->line('');

        $hasUnexpectedItems = ($currentTotalInvoiceItems > 0);
        if ($hasUnexpectedItems) {
            $this->warn('WARNING: Database state changed after dry-run. Invoice items exist!');
        }

        // 2. Compare with Original Audit
        $currentAppointmentLinked = Invoice::whereNotNull('appointment_id')->count();
        $currentMissingAppt = Invoice::whereNotNull('appointment_id')->whereDoesntHave('appointment')->count();
        $currentMissingService = Invoice::whereHas('appointment', function ($q) {
            $q->whereNull('service_id')->orWhereDoesntHave('service');
        })->count();

        $currentTotalAmount = (float) (Invoice::sum('total_amount') ?? 0);
        $currentPaidAmount = (float) (Invoice::sum('paid_amount') ?? 0);

        $this->info('--- VERIFICATION 2: COMPARISON WITH ORIGINAL AUDIT ---');
        $this->line(sprintf('%-30s | %-15s | %-15s | %-10s', 'Metric', 'Original Audit', 'Current State', 'Status'));
        $this->line(str_repeat('-', 78));

        $metrics = [
            ['Total invoices', $originalAudit['total_invoices'], $currentTotalInvoices],
            ['Total invoice_items', $originalAudit['total_items'], $currentTotalInvoiceItems],
            ['Appointment-linked', $originalAudit['appointment_linked'], $currentAppointmentLinked],
            ['Missing appointment', $originalAudit['missing_appointment'], $currentMissingAppt],
            ['Missing service', $originalAudit['missing_service'], $currentMissingService],
            ['Total invoice amount', $currency . number_format($originalAudit['total_amount'], 2), $currency . number_format($currentTotalAmount, 2)],
            ['Total paid amount', $currency . number_format($originalAudit['paid_amount'], 2), $currency . number_format($currentPaidAmount, 2)],
        ];

        $auditMatches = true;
        foreach ($metrics as [$label, $orig, $curr]) {
            $match = ($orig === $curr);
            if (!$match) $auditMatches = false;
            $this->line(sprintf('%-30s | %-15s | %-15s | %-10s', $label, $orig, $curr, $match ? 'MATCH' : 'MISMATCH'));
        }
        $this->line('');

        // 3. Financial Snapshot Check (8 Invoices)
        $this->info('--- VERIFICATION 3: INVOICE FINANCIAL INTEGRITY CHECK ---');
        $this->line(sprintf('%-4s | %-18s | %-10s | %-10s | %-12s | %-10s', 'ID', 'Invoice Number', 'Total', 'Paid', 'Status', 'Snapshot'));
        $this->line(str_repeat('-', 76));

        $financialTotalChanges = 0;
        $paidAmountChanges = 0;
        $statusChanges = 0;
        $subtotalChanges = 0;
        $discountChanges = 0;
        $taxChanges = 0;

        $invoices = Invoice::with(['appointment.service', 'staff', 'items'])->orderBy('id')->get();

        foreach ($invoices as $inv) {
            $orig = $originalSnapshot[$inv->id] ?? null;

            if (!$orig) {
                $this->error(sprintf('Invoice ID %d was NOT in the original audit snapshot!', $inv->id));
                $financialTotalChanges++;
                continue;
            }

            $currentTotal = (float) $inv->total_amount;
            $currentPaid = (float) $inv->paid_amount;
            $currentStatus = (string) $inv->status;
            $currentSubtotal = $inv->subtotal !== null ? (float) $inv->subtotal : null;
            $currentDiscount = (float) ($inv->discount_amount ?? 0);
            $currentTax = (float) ($inv->tax_amount ?? 0);

            $totalMatch = (abs($currentTotal - $orig['total']) < 0.0001);
            $paidMatch = (abs($currentPaid - $orig['paid']) < 0.0001);
            $statusMatch = ($currentStatus === $orig['status']);
            $subtotalMatch = ($currentSubtotal === $orig['subtotal']);
            $discountMatch = (abs($currentDiscount - $orig['discount']) < 0.0001);
            $taxMatch = (abs($currentTax - $orig['tax']) < 0.0001);

            if (!$totalMatch) $financialTotalChanges++;
            if (!$paidMatch) $paidAmountChanges++;
            if (!$statusMatch) $statusChanges++;
            if (!$subtotalMatch) $subtotalChanges++;
            if (!$discountMatch) $discountChanges++;
            if (!$taxMatch) $taxChanges++;

            $allMatched = ($totalMatch && $paidMatch && $statusMatch && $subtotalMatch && $discountMatch && $taxMatch);

            $this->line(sprintf(
                '%-4d | %-18s | %s%-9.2f | %s%-9.2f | %-12s | %s',
                $inv->id,
                $inv->invoice_number,
                $currency,
                $currentTotal,
                $currency,
                $currentPaid,
                $currentStatus,
                $allMatched ? 'UNCHANGED' : 'CHANGED'
            ));
        }
        $this->line('');

        // 4. Payment Records Verification
        $this->info('--- VERIFICATION 4: PAYMENT RECORDS CHECK ---');
        $paymentCount = PaymentRecord::count();
        $paymentSum = (float) (PaymentRecord::sum('amount') ?? 0);
        $this->line(sprintf('%-35s : %d', 'Payment records count', $paymentCount));
        $this->line(sprintf('%-35s : %s%s', 'Sum of payment amounts', $currency, number_format($paymentSum, 2)));

        // Verify that sum of payments equals the paid amount across all paid invoices
        $expectedPaidSum = $originalAudit['paid_amount'];
        $paymentSumMatches = (abs($paymentSum - $expectedPaidSum) < 0.0001);
        $this->line(sprintf('%-35s : %s', 'Matches total collected paid_amount', $paymentSumMatches ? 'YES' : 'NO'));
        $this->line('');

        // 5. Existing Invoice Items Inspection
        $this->info('--- VERIFICATION 5: INVOICE ITEMS STATE ---');
        if ($currentTotalInvoiceItems === 0) {
            $this->line('No invoice items currently exist in the database (as expected prior to backfill).');
        } else {
            $this->warn(sprintf('Found %d invoice items in table `invoice_items`:', $currentTotalInvoiceItems));
            $items = InvoiceItem::with('invoice')->get();
            foreach ($items as $item) {
                $this->line(sprintf(
                    '  Item ID: %d | Inv ID: %d (%s) | Svc ID: %s | Description: %s | Qty: %s | Unit: %s%s | Total: %s%s',
                    $item->id,
                    $item->invoice_id,
                    $item->invoice?->invoice_number ?? 'N/A',
                    $item->service_id ?? 'NULL',
                    $item->description,
                    $item->quantity,
                    $currency,
                    number_format((float) $item->unit_price, 2),
                    $currency,
                    number_format((float) $item->line_total, 2)
                ));
            }
        }
        $this->line('');

        // 6. Dry-Run Recheck (Proposed items calculation)
        $this->info('--- VERIFICATION 6: PROPOSED BACKFILL ITEM RECHECK ---');
        $dryRunMatches = 0;
        $dryRunMismatches = 0;

        foreach ($invoices as $inv) {
            $proposedData = [
                'invoice_id'      => $inv->id,
                'service_id'      => $inv->appointment?->service_id,
                'staff_id'        => $inv->staff_id,
                'description'     => $inv->appointment?->service?->name ?? 'Service',
                'quantity'        => 1.00,
                'unit_price'      => (float) $inv->total_amount,
                'discount_amount' => 0.00,
                'tax_rate'        => 0.00,
            ];

            $calc = $calculator->calculateItem($proposedData);
            $diff = round(abs($calc['line_total'] - (float) $inv->total_amount), 2);

            if ($diff === 0.00) {
                $dryRunMatches++;
            } else {
                $dryRunMismatches++;
                $this->error(sprintf('Mismatch on Invoice ID %d: Line total %s != Invoice total %s', $inv->id, $calc['line_total'], $inv->total_amount));
            }
        }
        $this->line(sprintf('Dry-run recheck matches: %d / %d (Mismatches: %d)', $dryRunMatches, count($invoices), $dryRunMismatches));
        $this->line('');

        // 7. Invoice #66 Special Check
        $this->info('--- VERIFICATION 7: INVOICE #66 SPECIAL CHECK ---');
        $inv66 = Invoice::with('appointment.service')->find(66);
        $inv66Total = (float) ($inv66?->total_amount ?? 0);
        $inv66SvcPrice = (float) ($inv66?->appointment?->service?->price ?? 0);
        $inv66Preserved = ($inv66 && abs($inv66Total - 101.70) < 0.0001);

        $this->line(sprintf('%-35s : %d', 'Invoice ID', 66));
        $this->line(sprintf('%-35s : %s', 'Invoice Number', $inv66?->invoice_number ?? 'NOT FOUND'));
        $this->line(sprintf('%-35s : %s%s', 'Historical Invoice Total', $currency, number_format($inv66Total, 2)));
        $this->line(sprintf('%-35s : %s%s', 'Current Service Price', $currency, number_format($inv66SvcPrice, 2)));
        $this->line(sprintf('%-35s : %s', 'Historical Total Preserved ($101.70)', $inv66Preserved ? 'YES' : 'NO'));
        $this->line('');

        // 8. Idempotency Readiness
        $this->info('--- VERIFICATION 8: IDEMPOTENCY READINESS ---');
        $this->line('Command logic checks `$invoice->items()->exists()` before creating any item.');
        $this->line('Any invoice that already possesses items will be skipped cleanly.');
        $this->line('Execution runs inside individual per-invoice DB transactions.');
        $this->line('');

        // Final Summary
        $isPass = (
            $currentTotalInvoices === 8 &&
            $currentTotalInvoiceItems === 0 &&
            $financialTotalChanges === 0 &&
            $paidAmountChanges === 0 &&
            $statusChanges === 0 &&
            $subtotalChanges === 0 &&
            $discountChanges === 0 &&
            $taxChanges === 0 &&
            $paymentSumMatches &&
            $dryRunMatches === 8 &&
            $dryRunMismatches === 0 &&
            $inv66Preserved
        );

        $this->line('==================================================');
        $this->line('STEP 3 — BACKFILL VERIFICATION');
        $this->line('==================================================');
        $this->line(sprintf('%-35s : %d', 'Original invoice count', 8));
        $this->line(sprintf('%-35s : %d', 'Current invoice count', $currentTotalInvoices));
        $this->line('');
        $this->line(sprintf('%-35s : %d', 'Original invoice_items count', 0));
        $this->line(sprintf('%-35s : %d', 'Current invoice_items count', $currentTotalInvoiceItems));
        $this->line('');
        $this->line(sprintf('%-35s : %d', 'Invoices with existing items', $invoicesWithItems));
        $this->line(sprintf('%-35s : %d', 'Invoices still without items', $invoicesWithoutItems));
        $this->line('');
        $this->line(sprintf('%-35s : %d', 'Financial total changes', $financialTotalChanges));
        $this->line(sprintf('%-35s : %d', 'Paid amount changes', $paidAmountChanges));
        $this->line(sprintf('%-35s : %d', 'Status changes', $statusChanges));
        $this->line('');
        $this->line(sprintf('%-35s : %s', 'Payment record changes', $paymentSumMatches ? 'NONE' : 'CHANGED'));
        $this->line('');
        $this->line(sprintf('%-35s : %d / 8', 'Dry-run financial matches', $dryRunMatches));
        $this->line(sprintf('%-35s : %d', 'Financial mismatches', $dryRunMismatches));
        $this->line('');
        $this->line(sprintf('%-35s : %s%s', 'Invoice #66 historical total', $currency, number_format($inv66Total, 2)));
        $this->line(sprintf('%-35s : %s%s', 'Current service price', $currency, number_format($inv66SvcPrice, 2)));
        $this->line(sprintf('%-35s : %s', 'Invoice #66 total preserved', $inv66Preserved ? 'YES' : 'NO'));
        $this->line('');
        $this->line('==================================================');

        if ($isPass) {
            $this->info('VERIFICATION RESULT: PASS');
        } else {
            $this->error('VERIFICATION RESULT: FAIL');
        }

        $this->line('==================================================');
        $this->line('');

        return $isPass ? Command::SUCCESS : Command::FAILURE;
    }
}

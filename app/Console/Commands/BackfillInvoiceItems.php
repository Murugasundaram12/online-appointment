<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Services\InvoiceCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInvoiceItems extends Command
{
    protected $signature = 'invoices:backfill-items
                            {--dry-run : Simulate the backfill without modifying database records}
                            {--execute : Perform the actual database backfill}';

    protected $description = 'Safely reconstruct missing legacy invoice items from appointment service relationships';

    public function handle(InvoiceCalculationService $calculator): int
    {
        $isExecute = $this->option('execute');

        if (!$isExecute) {
            return $this->handleDryRun($calculator);
        }

        return $this->handleExecute($calculator);
    }

    private function handleDryRun(InvoiceCalculationService $calculator): int
    {
        $this->line('');
        $this->line('==================================================');
        $this->line('STEP 2 — LEGACY INVOICE BACKFILL (DRY RUN ONLY)');
        $this->line('==================================================');
        $this->line('Mode: DRY RUN (Read-only simulation)');
        $this->line('Database writes: DISABLED');
        $this->line('');

        $currency = BusinessSetting::where('key', 'currency')->value('value') ?: '$';

        $invoices = Invoice::with(['appointment.service', 'client', 'staff', 'items'])
            ->orderBy('id')
            ->get();

        $auditEligibleCount = 8;
        $currentlyEligibleCount = 0;
        $wouldCreateCount = 0;
        $mismatchCount = 0;
        $skippedCount = 0;
        $alreadyHasItemsCount = 0;
        $missingAppointmentCount = 0;
        $missingServiceCount = 0;
        $financialAnomalyCount = 0;

        $totalHistoricalAmount = 0.0;
        $totalProposedAmount = 0.0;

        foreach ($invoices as $invoice) {
            $total = (float) $invoice->total_amount;
            $paid = (float) $invoice->paid_amount;
            $hasItems = $invoice->items->isNotEmpty();

            // Check Financial Anomaly
            if ($paid > ($total + 0.0001) || $total < 0 || $paid < 0 || !in_array($invoice->status, ['outstanding', 'partially_paid', 'paid', 'void'], true) || !$invoice->client || !$invoice->staff) {
                $financialAnomalyCount++;
                $skippedCount++;
                continue;
            }

            // Check Already has items
            if ($hasItems) {
                $alreadyHasItemsCount++;
                $skippedCount++;
                continue;
            }

            // Check Appointment presence
            if (is_null($invoice->appointment_id) || !$invoice->appointment) {
                $missingAppointmentCount++;
                $skippedCount++;
                continue;
            }

            // Check Service presence
            if (!$invoice->appointment->service) {
                $missingServiceCount++;
                $skippedCount++;
                continue;
            }

            // Eligible for backfill calculation
            $currentlyEligibleCount++;

            $service = $invoice->appointment->service;
            $currentServicePrice = (float) ($service->price ?? 0);
            $historicalInvoiceTotal = (float) $invoice->total_amount;

            // Proposed item attributes (historical invoice total is source of truth)
            $proposedItemData = [
                'invoice_id'      => $invoice->id,
                'service_id'      => $service->id,
                'staff_id'        => $invoice->staff_id,
                'description'     => $service->name,
                'quantity'        => 1.00,
                'unit_price'      => $historicalInvoiceTotal,
                'discount_amount' => 0.00,
                'tax_rate'        => 0.00,
            ];

            // Verify using calculation engine
            $calculatedItem = $calculator->calculateItem($proposedItemData);
            $calculatedTotal = $calculatedItem['line_total'];
            $difference = round(abs($calculatedTotal - $historicalInvoiceTotal), 2);

            $isSafe = ($difference === 0.00);

            if ($isSafe) {
                $resultStatus = 'SAFE';
                $wouldCreateCount++;
                $totalHistoricalAmount += $historicalInvoiceTotal;
                $totalProposedAmount += $calculatedTotal;
            } else {
                $resultStatus = 'FINANCIAL MISMATCH — MANUAL REVIEW REQUIRED';
                $mismatchCount++;
                $skippedCount++;
            }

            // Print detailed record for invoice
            $this->line('--------------------------------------------------');
            $this->line(sprintf('Invoice #%d (%s)', $invoice->id, $invoice->invoice_number));
            $this->line('--------------------------------------------------');
            $this->line(sprintf('Appointment ID          : %s', $invoice->appointment_id));
            $this->line(sprintf('Service ID              : %s', $service->id));
            $this->line(sprintf('Service                 : %s', $service->name));
            $this->line('');
            $this->line(sprintf('Current Service Price   : %s%s', $currency, number_format($currentServicePrice, 2)));
            $this->line(sprintf('Historical Invoice Total: %s%s', $currency, number_format($historicalInvoiceTotal, 2)));
            $this->line(sprintf('Paid Amount             : %s%s', $currency, number_format($paid, 2)));
            $this->line(sprintf('Status                  : %s', $invoice->status));
            $this->line('');
            $this->comment('PROPOSED ITEM');
            $this->line(sprintf('Quantity                : %s', number_format($calculatedItem['quantity'], 2)));
            $this->line(sprintf('Unit Price              : %s%s', $currency, number_format($calculatedItem['unit_price'], 2)));
            $this->line(sprintf('Discount                : %s%s', $currency, number_format($calculatedItem['discount_amount'], 2)));
            $this->line(sprintf('Tax Rate                : %s%%', number_format($calculatedItem['tax_rate'], 2)));
            $this->line(sprintf('Tax Amount              : %s%s', $currency, number_format($calculatedItem['tax_amount'], 2)));
            $this->line(sprintf('Line Total              : %s%s', $currency, number_format($calculatedItem['line_total'], 2)));
            $this->line('');
            $this->line(sprintf('Calculated Total        : %s%s', $currency, number_format($calculatedTotal, 2)));
            $this->line(sprintf('Difference              : %s%s', $currency, number_format($difference, 2)));
            $this->line('');
            if ($isSafe) {
                $this->info(sprintf('Result                  : %s', $resultStatus));
            } else {
                $this->error(sprintf('Result                  : %s', $resultStatus));
            }
            $this->line('');
        }

        $totalDiff = round(abs($totalHistoricalAmount - $totalProposedAmount), 2);

        // Summary Output
        $this->line('==================================================');
        $this->line('STEP 2 — DRY RUN SUMMARY');
        $this->line('==================================================');
        $this->line(sprintf('%-35s : %d', 'Audit eligible invoices', $auditEligibleCount));
        $this->line(sprintf('%-35s : %d', 'Currently eligible', $currentlyEligibleCount));
        $this->line('');
        $this->line(sprintf('%-35s : %d', 'Would create invoice items', $wouldCreateCount));
        $this->line(sprintf('%-35s : %d', 'Financial mismatches', $mismatchCount));
        $this->line(sprintf('%-35s : %d', 'Skipped', $skippedCount));
        $this->line(sprintf('  %-33s : %d', 'Already has items', $alreadyHasItemsCount));
        $this->line(sprintf('  %-33s : %d', 'Missing appointment', $missingAppointmentCount));
        $this->line(sprintf('  %-33s : %d', 'Missing service', $missingServiceCount));
        $this->line(sprintf('  %-33s : %d', 'Financial anomalies', $financialAnomalyCount));
        $this->line('');
        $this->line(sprintf('%-35s : %s%s', 'Total historical invoice amount', $currency, number_format($totalHistoricalAmount, 2)));
        $this->line(sprintf('%-35s : %s%s', 'Total proposed line amount', $currency, number_format($totalProposedAmount, 2)));
        $this->line(sprintf('%-35s : %s%s', 'Difference', $currency, number_format($totalDiff, 2)));
        $this->line('');
        $this->info(sprintf('%-35s : NONE', 'Database changes'));
        $this->line('==================================================');
        $this->line('');

        return Command::SUCCESS;
    }

    private function handleExecute(InvoiceCalculationService $calculator): int
    {
        $this->line('');
        $this->line('==================================================');
        $this->line('STEP 4 — ACTUAL INVOICE ITEM BACKFILL EXECUTION');
        $this->line('==================================================');
        $this->line('Mode: LIVE EXECUTION (--execute)');
        $this->line('');

        $currency = BusinessSetting::where('key', 'currency')->value('value') ?: '$';

        // Pre-Execution Baseline Capture
        $invoicesBeforeCount = Invoice::count();
        $itemsBeforeCount = InvoiceItem::count();
        $totalAmountBefore = (float) (Invoice::sum('total_amount') ?? 0);
        $paidAmountBefore = (float) (Invoice::sum('paid_amount') ?? 0);
        $paymentsBeforeCount = PaymentRecord::count();
        $paymentsBeforeSum = (float) (PaymentRecord::sum('amount') ?? 0);

        $baselineInvoices = Invoice::with(['appointment.service'])->orderBy('id')->get();

        $createdCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($baselineInvoices as $invSnapshot) {
            $invId = $invSnapshot->id;

            // Individual Database Transaction per Invoice
            DB::beginTransaction();

            try {
                // Check 1: Invoice still exists and lock for update
                $invoice = Invoice::with(['appointment.service', 'client', 'staff', 'items'])->lockForUpdate()->find($invId);

                if (!$invoice) {
                    $this->warn("Invoice #{$invId} no longer exists. Skipping.");
                    $skippedCount++;
                    DB::rollBack();
                    continue;
                }

                // Check 2: Invoice has no existing items
                if ($invoice->items()->exists()) {
                    $this->line("Invoice #{$invId} already has items. Skipping.");
                    $skippedCount++;
                    DB::rollBack();
                    continue;
                }

                // Check 3: Appointment still exists
                if (is_null($invoice->appointment_id) || !$invoice->appointment) {
                    $this->warn("Invoice #{$invId} has missing appointment. Skipping.");
                    $skippedCount++;
                    DB::rollBack();
                    continue;
                }

                // Check 4: Service still exists
                if (!$invoice->appointment->service) {
                    $this->warn("Invoice #{$invId} has missing service. Skipping.");
                    $skippedCount++;
                    DB::rollBack();
                    continue;
                }

                // Check 5: Financial values read
                $total = (float) $invoice->total_amount;
                $paid = (float) $invoice->paid_amount;

                if ($paid > ($total + 0.0001) || $total < 0 || $paid < 0 || !in_array($invoice->status, ['outstanding', 'partially_paid', 'paid', 'void'], true) || !$invoice->client || !$invoice->staff) {
                    $this->warn("Invoice #{$invId} has financial anomalies. Skipping.");
                    $skippedCount++;
                    DB::rollBack();
                    continue;
                }

                // Check 6: Proposed item attributes (historical invoice total is source of truth)
                $service = $invoice->appointment->service;

                $item = InvoiceItem::create([
                    'invoice_id'        => $invoice->id,
                    'service_id'        => $service->id,
                    'staff_id'          => $invoice->staff_id,
                    'quotation_item_id' => null,
                    'description'       => $service->name,
                    'quantity'          => 1.00,
                    'unit_price'        => $total,
                    'discount_amount'   => 0.00,
                    'tax_rate'          => 0.00,
                    'tax_amount'        => 0.00,
                    'line_total'        => $total,
                    'sort_order'        => 1,
                ]);

                // Verify created item with calculation service
                $calc = $calculator->calculateItem($item);
                $diff = round(abs($calc['line_total'] - $total), 2);

                if ($diff !== 0.00) {
                    throw new \Exception("Line total mismatch on created item ID {$item->id}: {$calc['line_total']} vs {$total}");
                }

                // Verify invoice header is completely unchanged
                $invoice->refresh();
                if (round(abs((float) $invoice->total_amount - $total), 2) !== 0.00 || round(abs((float) $invoice->paid_amount - $paid), 2) !== 0.00) {
                    throw new \Exception("Invoice financial total or paid amount changed unexpectedly on invoice ID {$invoice->id}");
                }

                DB::commit();

                // Required execution output format
                $this->line(sprintf('Invoice #%d (%s)', $invoice->id, $invoice->invoice_number));
                $this->info('Result      : CREATED');
                $this->line(sprintf('Item ID     : %d', $item->id));
                $this->line(sprintf('Line Total  : %s%s', $currency, number_format((float) $item->line_total, 2)));
                $this->line('');
                $createdCount++;

            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error(sprintf('Invoice #%d execution failed: %s', $invId, $e->getMessage()));
                $failedCount++;
            }
        }

        // POST-EXECUTION READ-ONLY VERIFICATION
        $this->line('==================================================');
        $this->line('POST-EXECUTION READ-ONLY VERIFICATION');
        $this->line('==================================================');

        $invoicesAfterCount = Invoice::count();
        $itemsAfterCount = InvoiceItem::count();
        $totalAmountAfter = (float) (Invoice::sum('total_amount') ?? 0);
        $paidAmountAfter = (float) (Invoice::sum('paid_amount') ?? 0);
        $paymentsAfterCount = PaymentRecord::count();
        $paymentsAfterSum = (float) (PaymentRecord::sum('amount') ?? 0);

        $invoiceCountChanged = ($invoicesBeforeCount !== $invoicesAfterCount);
        $totalAmountChanged = (abs($totalAmountBefore - $totalAmountAfter) > 0.0001);
        $paidAmountChanged = (abs($paidAmountBefore - $paidAmountAfter) > 0.0001);
        $paymentsChanged = ($paymentsBeforeCount !== $paymentsAfterCount || abs($paymentsBeforeSum - $paymentsAfterSum) > 0.0001);

        $statusesChanged = false;
        $eachHasOneItem = true;
        $allInvoices = Invoice::with('items')->get();

        foreach ($allInvoices as $checkInv) {
            $origSnapshot = $baselineInvoices->firstWhere('id', $checkInv->id);
            if ($origSnapshot && $origSnapshot->status !== $checkInv->status) {
                $statusesChanged = true;
            }
            if ($checkInv->items->count() !== 1) {
                $eachHasOneItem = false;
            }
        }

        $totalItemsAmount = (float) (InvoiceItem::sum('line_total') ?? 0);
        $financialDifference = round(abs($totalAmountAfter - $totalItemsAmount), 2);

        // Invoice #66 Specific Check
        $inv66 = Invoice::with(['items', 'appointment.service'])->find(66);
        $inv66Total = (float) ($inv66?->total_amount ?? 0);
        $inv66ItemPrice = (float) ($inv66?->items->first()?->unit_price ?? 0);
        $inv66ItemTotal = (float) ($inv66?->items->first()?->line_total ?? 0);
        $inv66SvcPrice = (float) ($inv66?->appointment?->service?->price ?? 0);

        $inv66Preserved = (
            abs($inv66Total - 101.70) < 0.0001 &&
            abs($inv66ItemPrice - 101.70) < 0.0001 &&
            abs($inv66ItemTotal - 101.70) < 0.0001 &&
            abs($inv66SvcPrice - 62.15) < 0.0001
        );

        $this->line(sprintf('Invoice count check               : %d -> %d (%s)', $invoicesBeforeCount, $invoicesAfterCount, $invoiceCountChanged ? 'CHANGED' : 'OK'));
        $this->line(sprintf('Invoice item count check          : %d -> %d', $itemsBeforeCount, $itemsAfterCount));
        $this->line(sprintf('Each invoice has exactly 1 item   : %s', $eachHasOneItem ? 'YES' : 'NO'));
        $this->line(sprintf('Invoice totals sum check          : %s%s -> %s%s', $currency, number_format($totalAmountBefore, 2), $currency, number_format($totalAmountAfter, 2)));
        $this->line(sprintf('Proposed item amount sum check    : %s%s', $currency, number_format($totalItemsAmount, 2)));
        $this->line(sprintf('Financial difference              : %s%s', $currency, number_format($financialDifference, 2)));
        $this->line(sprintf('Paid amounts sum check            : %s%s -> %s%s', $currency, number_format($paidAmountBefore, 2), $currency, number_format($paidAmountAfter, 2)));
        $this->line(sprintf('Payment records check             : %d records, %s%s (%s)', $paymentsAfterCount, $currency, number_format($paymentsAfterSum, 2), $paymentsChanged ? 'CHANGED' : 'UNCHANGED'));
        $this->line(sprintf('Invoice #66 total preserved       : %s ($101.70 preserved, svc catalog: $62.15)', $inv66Preserved ? 'YES' : 'NO'));
        $this->line('');

        $duplicateItemsCount = Invoice::has('items', '>', 1)->count();

        $isPass = (
            !$invoiceCountChanged &&
            !$totalAmountChanged &&
            !$paidAmountChanged &&
            !$paymentsChanged &&
            !$statusesChanged &&
            $eachHasOneItem &&
            $financialDifference === 0.00 &&
            $inv66Preserved &&
            $failedCount === 0 &&
            $duplicateItemsCount === 0 &&
            ($createdCount > 0 || $skippedCount === 8)
        );

        // Required Final Report Block
        $this->line('==================================================');
        $this->line('PHASE 1B — ACTUAL BACKFILL COMPLETE');
        $this->line('==================================================');
        $this->line(sprintf('%-33s : %d', 'Invoices before', $invoicesBeforeCount));
        $this->line(sprintf('%-33s : %d', 'Invoices after', $invoicesAfterCount));
        $this->line(sprintf('%-33s : %s', 'Invoice count changed', $invoiceCountChanged ? 'YES' : 'NO'));
        $this->line('');
        $this->line(sprintf('%-33s : %d', 'Invoice items before', $itemsBeforeCount));
        $this->line(sprintf('%-33s : %d', 'Invoice items after', $itemsAfterCount));
        $this->line('');
        $this->line(sprintf('%-33s : %d', 'New invoice items created', $createdCount));
        $this->line(sprintf('%-33s : %d', 'Skipped', $skippedCount));
        $this->line(sprintf('%-33s : %d', 'Failures / rollbacks', $failedCount));
        $this->line('');
        $this->line(sprintf('%-33s : %s%s', 'Total invoice amount before', $currency, number_format($totalAmountBefore, 2)));
        $this->line(sprintf('%-33s : %s%s', 'Total invoice amount after', $currency, number_format($totalAmountAfter, 2)));
        $this->line('');
        $this->line(sprintf('%-33s : %s%s', 'Total paid amount before', $currency, number_format($paidAmountBefore, 2)));
        $this->line(sprintf('%-33s : %s%s', 'Total paid amount after', $currency, number_format($paidAmountAfter, 2)));
        $this->line('');
        $this->line(sprintf('%-33s : %d', 'Payment records before', $paymentsBeforeCount));
        $this->line(sprintf('%-33s : %d', 'Payment records after', $paymentsAfterCount));
        $this->line('');
        $this->line(sprintf('%-33s : %s', 'Invoice statuses changed', $statusesChanged ? 'YES' : 'NO'));
        $this->line(sprintf('%-33s : %s', 'Invoice totals changed', $totalAmountChanged ? 'YES' : 'NO'));
        $this->line(sprintf('%-33s : %s', 'Paid amounts changed', $paidAmountChanged ? 'YES' : 'NO'));
        $this->line('');
        $this->line(sprintf('%-33s : %s%s', 'Financial difference', $currency, number_format($financialDifference, 2)));
        $this->line('');
        $this->line(sprintf('%-33s : %s', 'Each invoice has exactly 1 item', $eachHasOneItem ? 'YES' : 'NO'));
        $this->line(sprintf('%-33s : %d', 'Duplicate items', $duplicateItemsCount));
        $this->line('');
        $this->line(sprintf('%-33s : %s', 'Invoice #66 total preserved', $inv66Preserved ? 'YES' : 'NO'));
        $this->line('');
        $this->line('==================================================');
        if ($isPass) {
            $this->info('PHASE 1B RESULT: PASS');
        } else {
            $this->error('PHASE 1B RESULT: FAIL');
        }
        $this->line('==================================================');
        $this->line('');

        return $isPass ? Command::SUCCESS : Command::FAILURE;
    }
}

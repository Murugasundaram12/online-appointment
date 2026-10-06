<?php

namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditInvoiceBackfill extends Command
{
    protected $signature = 'invoices:audit-backfill';

    protected $description = 'Perform a read-only audit of legacy invoices for invoice item backfill readiness';

    public function handle(): int
    {
        $this->line('');
        $this->line('==================================================');
        $this->line('LEGACY INVOICE BACKFILL — READ-ONLY AUDIT');
        $this->line('==================================================');

        $currency = BusinessSetting::where('key', 'currency')->value('value') ?: '$';

        $totalInvoices = Invoice::count();
        $totalInvoiceItems = InvoiceItem::count();
        $invoicesWithItems = Invoice::has('items')->count();
        $invoicesWithZeroItems = Invoice::doesntHave('items')->count();

        $appointmentLinkedCount = Invoice::whereNotNull('appointment_id')->count();
        $validAppointmentCount = Invoice::whereHas('appointment')->count();
        $missingAppointmentCount = Invoice::whereNotNull('appointment_id')->whereDoesntHave('appointment')->count();

        $appointmentLessCount = Invoice::whereNull('appointment_id')->count();

        $validServiceCount = Invoice::whereHas('appointment.service')->count();
        $missingServiceCount = Invoice::whereHas('appointment', function ($q) {
            $q->whereNull('service_id')->orWhereDoesntHave('service');
        })->count();

        // Financial metrics
        $sumTotalAmount = (float) (Invoice::sum('total_amount') ?? 0);
        $minTotalAmount = (float) (Invoice::min('total_amount') ?? 0);
        $maxTotalAmount = (float) (Invoice::max('total_amount') ?? 0);
        $zeroTotalCount = Invoice::where('total_amount', 0)->count();
        $negativeTotalCount = Invoice::where('total_amount', '<', 0)->count();

        $sumPaidAmount = (float) (Invoice::sum('paid_amount') ?? 0);
        $minPaidAmount = (float) (Invoice::min('paid_amount') ?? 0);
        $maxPaidAmount = (float) (Invoice::max('paid_amount') ?? 0);
        $zeroPaidCount = Invoice::where('paid_amount', 0)->count();
        $overpaidCount = Invoice::whereColumn('paid_amount', '>', 'total_amount')->count();

        // Categorize every invoice
        $categoryA = []; // Safe to backfill
        $categoryB = []; // Appointment exists but service is missing/unresolved
        $categoryC = []; // No appointment
        $categoryD = []; // Already has items
        $categoryE = []; // Financial anomaly

        $invoices = Invoice::with(['appointment.service', 'client', 'staff', 'items'])->get();

        foreach ($invoices as $invoice) {
            $total = (float) $invoice->total_amount;
            $paid = (float) $invoice->paid_amount;
            $hasItems = $invoice->items->isNotEmpty();

            // Check Financial Anomaly (Category E)
            $anomalyReasons = [];
            if ($paid > ($total + 0.0001)) {
                $anomalyReasons[] = "paid_amount ({$paid}) > total_amount ({$total})";
            }
            if ($total < 0) {
                $anomalyReasons[] = "negative total_amount ({$total})";
            }
            if ($paid < 0) {
                $anomalyReasons[] = "negative paid_amount ({$paid})";
            }
            if (!in_array($invoice->status, ['outstanding', 'partially_paid', 'paid', 'void'], true)) {
                $anomalyReasons[] = "invalid status ('{$invoice->status}')";
            }
            if (!$invoice->client) {
                $anomalyReasons[] = "missing client record (client_id: {$invoice->client_id})";
            }
            if (!$invoice->staff) {
                $anomalyReasons[] = "missing staff record (staff_id: {$invoice->staff_id})";
            }

            if (!empty($anomalyReasons)) {
                $categoryE[] = [
                    'invoice' => $invoice,
                    'reasons' => $anomalyReasons,
                ];
                continue;
            }

            // Category D: Already has items
            if ($hasItems) {
                $categoryD[] = $invoice;
                continue;
            }

            // Category C: No appointment (appointment_id IS NULL)
            if (is_null($invoice->appointment_id)) {
                $categoryC[] = $invoice;
                continue;
            }

            // Appointment ID is set, check appointment resolution
            if (!$invoice->appointment) {
                $categoryB[] = [
                    'invoice' => $invoice,
                    'reason' => "Appointment record missing (appointment_id: {$invoice->appointment_id})",
                ];
                continue;
            }

            // Appointment exists, check service resolution
            if (!$invoice->appointment->service) {
                $categoryB[] = [
                    'invoice' => $invoice,
                    'reason' => "Service record missing (service_id: " . ($invoice->appointment->service_id ?? 'NULL') . ")",
                ];
                continue;
            }

            // Category A: Safe to backfill
            $categoryA[] = $invoice;
        }

        // Summary Output
        $this->line(sprintf('%-38s : %d', 'Total invoices', $totalInvoices));
        $this->line(sprintf('%-38s : %d', 'Total invoice items', $totalInvoiceItems));
        $this->line(sprintf('%-38s : %d', 'Invoices already having items', $invoicesWithItems));
        $this->line(sprintf('%-38s : %d', 'Invoices with zero items', $invoicesWithZeroItems));
        $this->line('');

        $this->line(sprintf('%-38s : %d', 'Appointment-linked invoices', $appointmentLinkedCount));
        $this->line(sprintf('  %-36s : %d', 'Valid appointment', $validAppointmentCount));
        $this->line(sprintf('  %-36s : %d', 'Missing appointment', $missingAppointmentCount));
        $this->line('');

        $this->line(sprintf('%-38s : %d', 'Appointment-less invoices', $appointmentLessCount));
        $this->line('');

        $this->line(sprintf('%-38s : %d', 'Valid service records', $validServiceCount));
        $this->line(sprintf('%-38s : %d', 'Missing/null service', $missingServiceCount));
        $this->line('');

        $this->info('--- CLASSIFICATION BREAKDOWN ---');
        $this->line(sprintf('%-38s : %d', 'CATEGORY A (Safe to backfill)', count($categoryA)));
        $this->line(sprintf('%-38s : %d', 'CATEGORY B (Service unresolved)', count($categoryB)));
        $this->line(sprintf('%-38s : %d', 'CATEGORY C (No appointment)', count($categoryC)));
        $this->line(sprintf('%-38s : %d', 'CATEGORY D (Already has items)', count($categoryD)));
        $this->line(sprintf('%-38s : %d', 'CATEGORY E (Financial anomalies)', count($categoryE)));
        $this->line('');

        $this->info('--- FINANCIAL TOTALS (READ-ONLY) ---');
        $this->line(sprintf('%-38s : %s%s', 'Sum of invoice total_amount', $currency, number_format($sumTotalAmount, 2)));
        $this->line(sprintf('  %-36s : %s%s', 'Minimum total_amount', $currency, number_format($minTotalAmount, 2)));
        $this->line(sprintf('  %-36s : %s%s', 'Maximum total_amount', $currency, number_format($maxTotalAmount, 2)));
        $this->line(sprintf('  %-36s : %d', 'Invoices with total_amount = 0', $zeroTotalCount));
        $this->line(sprintf('  %-36s : %d', 'Invoices with negative total_amount', $negativeTotalCount));
        $this->line('');

        $this->line(sprintf('%-38s : %s%s', 'Sum of invoice paid_amount', $currency, number_format($sumPaidAmount, 2)));
        $this->line(sprintf('  %-36s : %s%s', 'Minimum paid_amount', $currency, number_format($minPaidAmount, 2)));
        $this->line(sprintf('  %-36s : %s%s', 'Maximum paid_amount', $currency, number_format($maxPaidAmount, 2)));
        $this->line(sprintf('  %-36s : %d', 'Invoices with paid_amount = 0', $zeroPaidCount));
        $this->line(sprintf('  %-36s : %d', 'Invoices where paid > total', $overpaidCount));
        $this->line('');

        // Status grouped summary
        $this->info('--- STATUS SUMMARY ---');
        $statuses = Invoice::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        foreach ($statuses as $row) {
            $statusName = $row->status ?? 'NULL';
            $this->line(sprintf('  %-36s : %d', $statusName, $row->count));
        }

        // Details for Category A (Safe to backfill)
        if (!empty($categoryA)) {
            $this->line('');
            $this->info('--- CATEGORY A DETAILS (SAFE TO BACKFILL) ---');
            foreach ($categoryA as $inv) {
                $serviceName = $inv->appointment?->service?->name ?? 'Service';
                $servicePrice = $inv->appointment?->service?->price ?? '0.00';
                $this->line(sprintf(
                    '  ID: %-3d | Number: %-18s | Appt: %-3d | Svc: %-20s | SvcPrice: %s%s | InvTotal: %s%s | Paid: %s%s | Status: %s',
                    $inv->id,
                    $inv->invoice_number,
                    $inv->appointment_id,
                    mb_strimwidth($serviceName, 0, 20, '...'),
                    $currency,
                    number_format((float) $servicePrice, 2),
                    $currency,
                    number_format((float) $inv->total_amount, 2),
                    $currency,
                    number_format((float) $inv->paid_amount, 2),
                    $inv->status
                ));
            }
        }

        // Details for Category B (Unresolved service/appointment)
        if (!empty($categoryB)) {
            $this->line('');
            $this->warn('--- CATEGORY B DETAILS (SERVICE UNRESOLVED) ---');
            foreach ($categoryB as $item) {
                $inv = $item['invoice'];
                $this->line(sprintf(
                    '  Invoice ID: %-5d | Number: %-18s | Appt ID: %-5s | Reason: %s',
                    $inv->id,
                    $inv->invoice_number,
                    $inv->appointment_id ?? 'NULL',
                    $item['reason']
                ));
            }
        }

        // Details for Category C (Appointment-less)
        if (!empty($categoryC)) {
            $this->line('');
            $this->comment('--- CATEGORY C DETAILS (NO APPOINTMENT) ---');
            foreach ($categoryC as $inv) {
                $this->line(sprintf(
                    '  Invoice ID: %-5d | Number: %-18s | Client: %-20s | Total: %s%s',
                    $inv->id,
                    $inv->invoice_number,
                    $inv->client?->name ?? 'Unknown',
                    $currency,
                    number_format((float) $inv->total_amount, 2)
                ));
            }
        }

        // Details for Category E (Financial Anomaly)
        if (!empty($categoryE)) {
            $this->line('');
            $this->error('--- CATEGORY E DETAILS (FINANCIAL ANOMALIES) ---');
            foreach ($categoryE as $item) {
                $inv = $item['invoice'];
                $this->line(sprintf(
                    '  Invoice ID: %-5d | Number: %-18s | Total: %s%s | Paid: %s%s | Reasons: %s',
                    $inv->id,
                    $inv->invoice_number,
                    $currency,
                    number_format((float) $inv->total_amount, 2),
                    $currency,
                    number_format((float) $inv->paid_amount, 2),
                    implode(', ', $item['reasons'])
                ));
            }
        }

        $this->line('');
        $this->line('==================================================');
        $this->info('NO DATABASE CHANGES WERE MADE');
        $this->line('==================================================');
        $this->line('');

        return Command::SUCCESS;
    }
}

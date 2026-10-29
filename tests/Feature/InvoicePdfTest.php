<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
use App\Models\PaymentRecord;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $admin;
    private Staff $staff;
    private Client $client;
    private ServiceCategory $category;
    private Service $service;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name'      => 'Downtown Medical Clinic',
            'address'   => '123 Health Ave, Suite 100',
            'phone'     => '555-123-4567',
            'email'     => 'downtown@clinic.test',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'Clinic Director',
            'email'        => 'director@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staff = Staff::create([
            'location_id'         => $this->location->id,
            'name'                => 'Dr. Sarah Connor',
            'email'               => 'sarah@clinic.test',
            'password'            => Hash::make('password123'),
            'access_level'        => 'staff',
            'is_active'           => true,
            'registration_number' => 'MED-98765',
        ]);

        $this->client = Client::create([
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'name'       => 'John Smith',
            'email'      => 'john.smith@example.com',
            'phone'      => '555-987-6543',
            'city'       => 'Toronto',
        ]);

        $this->category = ServiceCategory::create([
            'name'      => 'General Consultation',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'General Consultation',
            'price'               => 150.00,
            'duration_minutes'    => 30,
            'is_active'           => true,
        ]);

        $this->appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staff->id,
            'service_id'  => $this->service->id,
            'location_id' => $this->location->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(10, 30),
            'status'      => 'confirmed',
        ]);

        BusinessSetting::updateOrCreate(['key' => 'currency'], ['value' => '$']);
        BusinessSetting::updateOrCreate(['key' => 'business_name'], ['value' => 'Apex Health Group']);
    }

    /**
     * Helper to decompress and extract text from DomPDF binary stream output.
     */
    private function extractPdfText(string $pdfBinary): string
    {
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfBinary, $matches);
        $extracted = '';
        foreach ($matches[1] as $stream) {
            $uncompressed = @gzuncompress($stream);
            if ($uncompressed !== false) {
                $extracted .= $uncompressed . "\n";
            } else {
                $extracted .= $stream . "\n";
            }
        }

        return $extracted ?: $pdfBinary;
    }

    private function createSampleInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number'  => 'INV-2026-999',
            'client_id'       => $this->client->id,
            'staff_id'        => $this->staff->id,
            'appointment_id'  => $this->appointment->id,
            'subtotal'        => 150.00,
            'discount_amount' => 10.00,
            'tax_amount'      => 14.00,
            'total_amount'    => 154.00,
            'paid_amount'     => 100.00,
            'status'          => 'partially_paid',
            'issued_date'     => '2026-10-29',
            'due_date'        => '2026-11-29',
        ]);

        InvoiceItem::create([
            'invoice_id'      => $invoice->id,
            'service_id'      => $this->service->id,
            'staff_id'        => $this->staff->id,
            'description'     => 'General Consultation Deluxe',
            'quantity'        => 1,
            'unit_price'      => 150.00,
            'discount_amount' => 10.00,
            'tax_rate'        => 10.00,
            'tax_amount'      => 14.00,
            'line_total'      => 154.00,
        ]);

        PaymentRecord::create([
            'invoice_id'     => $invoice->id,
            'amount'         => 100.00,
            'payment_method' => 'card',
            'payment_date'   => '2026-10-29',
            'transaction_id' => 'TXN-ABC-12345',
        ]);

        return $invoice;
    }

    /** Test 1 — Show contains Print action and Download PDF link */
    public function test_show_contains_print_action_and_download_pdf_link(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertSee('window.print()', false);
        $response->assertSee('Print invoice');
        $response->assertSee(route('invoices.download', $invoice), false);
        $response->assertSee('Download invoice');
    }

    /** Test 2 — PDF download returns HTTP 200 and application/pdf */
    public function test_pdf_download_returns_http_200_and_pdf_mime_type(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.download', $invoice));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** Test 3 — Filename in Content-Disposition */
    public function test_pdf_download_sets_correct_content_disposition_filename(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.download', $invoice));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('content-disposition');
        $expectedFilename = "invoice-{$invoice->invoice_number}.pdf";

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString($expectedFilename, $contentDisposition);
    }

    /** Test 4 — Consistent data between Preview/Print and PDF */
    public function test_pdf_and_show_contain_consistent_metadata_branding_totals_and_items(): void
    {
        $invoice = $this->createSampleInvoice();

        // 1. Check Preview/Print web view
        $showResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.show', $invoice));

        $showResponse->assertStatus(200);
        $showResponse->assertSee($invoice->invoice_number);
        $showResponse->assertSee($this->client->name);
        $showResponse->assertSee($this->staff->name);
        $showResponse->assertSee('General Consultation Deluxe');
        $showResponse->assertSee('154.00'); // Total
        $showResponse->assertSee('100.00'); // Paid
        $showResponse->assertSee('54.00');  // Balance
        $showResponse->assertSee('Partially paid');
        $showResponse->assertSee('TXN-ABC-12345');

        // 2. Check Download PDF stream
        $pdfResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.download', $invoice));

        $pdfResponse->assertStatus(200);
        $pdfText = $this->extractPdfText($pdfResponse->getContent());

        $this->assertStringContainsString($invoice->invoice_number, $pdfText);
        $this->assertStringContainsString($this->client->name, $pdfText);
        $this->assertStringContainsString($this->staff->name, $pdfText);
        $this->assertStringContainsString('General Consultation Deluxe', $pdfText);
        $this->assertStringContainsString('154.00', $pdfText);
        $this->assertStringContainsString('100.00', $pdfText);
        $this->assertStringContainsString('54.00', $pdfText);
        $this->assertStringContainsString('Partially paid', $pdfText);
        $this->assertStringContainsString('TXN-ABC-12345', $pdfText);
    }

    /** Test 5 — Print CSS rules preserve layout and hide screen navigation */
    public function test_print_css_preserves_document_card_styling_and_hides_chrome(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.show', $invoice));

        $content = $response->getContent();

        // Screen-only media query prevents print and dompdf collapse
        $this->assertStringContainsString('@media not dompdf and screen and (max-width: 768px)', $content);

        // Print and DomPDF media query styles
        $this->assertStringContainsString('@media print, dompdf', $content);
        $this->assertStringContainsString('size: A4 portrait;', $content);
        $this->assertStringContainsString('font-family: Arial, Helvetica, sans-serif !important;', $content);
        $this->assertStringContainsString('border: 1px solid var(--invoice-border) !important;', $content);
        $this->assertStringContainsString('border-radius: 12px !important;', $content);
        $this->assertStringContainsString('padding: 22px 26px !important;', $content);
        $this->assertStringContainsString('.no-print', $content);
        $this->assertStringContainsString('.sidebar', $content);
        $this->assertStringContainsString('.app-topbar', $content);
        $this->assertStringContainsString('display: none !important;', $content);
    }

    /** Test 6 — Downloaded PDF preserves two-column layout and does not collapse to mobile */
    public function test_pdf_renders_two_column_layout_without_mobile_collapse(): void
    {
        $invoice = $this->createSampleInvoice();

        $pdfResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.download', $invoice));

        $pdfResponse->assertStatus(200);
        $pdfContent = $pdfResponse->getContent();

        // Extract BT ... Td coordinates for right-aligned card content
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfContent, $m);
        $foundRightColumn = false;
        foreach ($m[1] as $stream) {
            $u = @gzuncompress($stream);
            $s = $u !== false ? $u : $stream;
            if (strpos($s, 'Payment Status') !== false) {
                if (preg_match('/BT\s+([\d\.]+)\s+([\d\.]+)\s+Td[^\n]*\(' . preg_quote('Payment Status', '/') . '\)/', $s, $match)) {
                    $xCoord = (float) $match[1];
                    // On A4 page (~595 pt width), right column is positioned > 300 pt
                    $this->assertGreaterThan(300.0, $xCoord, "Payment Status should be positioned in right column (> 300 pt), got {$xCoord}");
                    $foundRightColumn = true;
                }
            }
        }

        $this->assertTrue($foundRightColumn, 'Payment Status coordinates were not located in PDF stream.');
    }

    /** Test 7 — Downloaded PDF content, headers, and tables remain within printable A4 boundaries without clipping */
    public function test_pdf_fits_within_a4_page_boundaries_without_right_edge_clipping(): void
    {
        $invoice = $this->createSampleInvoice();

        $pdfResponse = $this->actingAs($this->admin, 'staff')
            ->get(route('invoices.download', $invoice));

        $pdfResponse->assertStatus(200);
        $pdfContent = $pdfResponse->getContent();

        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfContent, $m);
        $foundInvoiceKicker = false;
        $foundAmountHeader = false;

        foreach ($m[1] as $stream) {
            $u = @gzuncompress($stream);
            $s = $u !== false ? $u : $stream;

            // Check document card rectangles do not exceed printable width (578.28 pt for 6mm margins)
            preg_match_all('/([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+re/', $s, $rects, PREG_SET_ORDER);
            foreach ($rects as $r) {
                $rx = (float) $r[1];
                $rw = (float) $r[3];
                $right = $rx + $rw;
                // Exclude full page canvas background (w ~ 595.28)
                if ($rw < 590.0) {
                    $this->assertLessThanOrEqual(578.5, $right, "Document container rectangle right edge ({$right} pt) exceeds A4 printable boundary.");
                }
            }

            // Check INVOICE kicker position
            if (preg_match('/BT\s+([\d\.]+)\s+([\d\.]+)\s+Td[^\n]*\(' . preg_quote('INVOICE', '/') . '\)/', $s, $match)) {
                $xCoord = (float) $match[1];
                $this->assertGreaterThan(450.0, $xCoord, "INVOICE title should be on the right side (> 450 pt), got {$xCoord}");
                $this->assertLessThanOrEqual(525.0, $xCoord, "INVOICE title X ({$xCoord} pt) must leave room for text width within 578 pt boundary.");
                $foundInvoiceKicker = true;
            }

            // Check AMOUNT column header position
            if (preg_match('/BT\s+([\d\.]+)\s+([\d\.]+)\s+Td[^\n]*\(' . preg_quote('AMOUNT', '/') . '\)/', $s, $match)) {
                $xCoord = (float) $match[1];
                $this->assertGreaterThan(500.0, $xCoord, "AMOUNT column header should be on the right side (> 500 pt), got {$xCoord}");
                $this->assertLessThanOrEqual(550.0, $xCoord, "AMOUNT column header X ({$xCoord} pt) must leave room for header width within 578 pt boundary.");
                $foundAmountHeader = true;
            }
        }

        $this->assertTrue($foundInvoiceKicker, 'INVOICE title was not located in PDF stream.');
        $this->assertTrue($foundAmountHeader, 'AMOUNT column header was not located in PDF stream.');
    }
}


<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\QuotationCreationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationPdfTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $admin;
    private Staff $staffA;
    private Staff $staffB;
    private Client $client;
    private ServiceCategory $category;
    private Service $serviceA;
    private Service $serviceB;
    private Appointment $appointment;
    private QuotationCreationService $quotationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name'      => 'Downtown Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name'      => 'Uptown Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Director Admin',
            'email'        => 'admin@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->staffA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Alice Downtown',
            'email'        => 'alice@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->staffB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob Uptown',
            'email'        => 'bob@clinic.test',
            'password'     => Hash::make('password123'),
            'access_level' => 'staff',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'          => 'Jane Doe Patient',
            'email'         => 'jane.doe@example.com',
            'phone'         => '555-0199',
            'address_line1' => '123 Main St',
            'city'          => 'Toronto',
            'status'        => 'active',
        ]);

        $this->category = ServiceCategory::create(['name' => 'Consultations']);

        $this->serviceA = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Dental Checkup',
            'price'               => 100.00,
            'duration_minutes'    => 30,
            'is_active'           => true,
        ]);

        $this->serviceB = Service::create([
            'service_category_id' => $this->category->id,
            'name'                => 'Physiotherapy Session',
            'price'               => 80.00,
            'duration_minutes'    => 45,
            'is_active'           => true,
        ]);

        $this->appointment = Appointment::create([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'service_id'  => $this->serviceA->id,
            'location_id' => $this->locationA->id,
            'start_time'  => Carbon::tomorrow()->setTime(10, 0),
            'end_time'    => Carbon::tomorrow()->setTime(10, 30),
            'status'      => 'confirmed',
        ]);

        $this->quotationService = app(QuotationCreationService::class);
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

    /** Test 1 — Show contains Print action and Download PDF link */
    public function test_show_contains_print_action_and_download_pdf_link(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'issued_date'    => '2026-10-06',
            'valid_until'    => '2026-11-05',
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Initial Assessment',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertSee('window.print()', false);
        $response->assertSee(route('quotations.download', $quotation), false);
        $response->assertSee('Print Quotation');
        $response->assertSee('Download PDF');
    }

    /** Test 2 — PDF download returns HTTP 200 and application/pdf */
    public function test_pdf_download_returns_http_200_and_pdf_mime_type(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Consultation Test',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** Test 3 — Filename in Content-Disposition */
    public function test_pdf_download_sets_correct_content_disposition_filename(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Consultation Test',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('content-disposition');
        $expectedFilename = "quotation-{$quotation->quotation_number}.pdf";

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString($expectedFilename, $contentDisposition);
    }

    /** Test 4 — Quotation metadata */
    public function test_pdf_contains_quotation_metadata(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => $this->appointment->id,
            'issued_date'    => '2026-10-06',
            'valid_until'    => '2026-11-05',
            'status'         => 'draft',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Initial Assessment',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        $this->assertStringContainsString($quotation->quotation_number, $pdfText);
        $this->assertStringContainsString($this->client->name, $pdfText);
        $this->assertStringContainsString($this->staffA->name, $pdfText);
        $this->assertStringContainsString('06 Oct 2026', $pdfText);
        $this->assertStringContainsString('05 Nov 2026', $pdfText);
        $this->assertStringContainsString('Draft', $pdfText);
    }

    /** Test 5 — Multi-item PDF */
    public function test_multi_item_quotation_renders_all_items_and_financial_snapshots_in_pdf(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Dental Exam Alpha',
                    'quantity'        => 2,
                    'unit_price'      => 50.00,
                    'discount_amount' => 10.00,
                    'tax_rate'        => 10.00,
                ],
                [
                    'service_id'      => $this->serviceB->id,
                    'description'     => 'Physio Therapy Beta',
                    'quantity'        => 1.5,
                    'unit_price'      => 40.00,
                    'discount_amount' => 0.00,
                    'tax_rate'        => 0.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        // Descriptions
        $this->assertStringContainsString('Dental Exam Alpha', $pdfText);
        $this->assertStringContainsString('Physio Therapy Beta', $pdfText);

        // Quantities
        $this->assertStringContainsString('2', $pdfText);
        $this->assertStringContainsString('1.5', $pdfText);

        // Rates
        $this->assertStringContainsString('50.00', $pdfText);
        $this->assertStringContainsString('40.00', $pdfText);

        // Line totals
        $this->assertStringContainsString('99.00', $pdfText);
        $this->assertStringContainsString('60.00', $pdfText);

        // Header totals
        $this->assertStringContainsString('160.00', $pdfText); // Subtotal
        $this->assertStringContainsString('10.00', $pdfText);  // Discount
        $this->assertStringContainsString('9.00', $pdfText);   // Tax
        $this->assertStringContainsString('159.00', $pdfText); // Total
    }

    /** Test 6 — Historical price protection */
    public function test_historical_price_protection_uses_persisted_rate_not_modified_catalog_price(): void
    {
        // Service price initially 100.00
        $this->serviceA->update(['price' => 100.00]);

        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Protected Price Item',
                    'quantity'    => 1,
                    'unit_price'  => 100.00,
                ],
            ],
        ]);

        // Service catalog price modified after creation to 150.00
        $this->serviceA->update(['price' => 150.00]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        // Must preserve historical rate 100.00 and NOT display altered catalog price 150.00
        $this->assertStringContainsString('100.00', $pdfText);
        $this->assertStringNotContainsString('150.00', $pdfText);
    }

    /** Test 7 — Decimal quantity */
    public function test_decimal_quantity_renders_correctly_in_pdf(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Decimal Quantity Item',
                    'quantity'    => 1.5,
                    'unit_price'  => 60.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        $this->assertStringContainsString('1.5', $pdfText);
        $this->assertStringContainsString('90.00', $pdfText);
    }

    /** Test 8 — Discount and tax */
    public function test_discount_and_tax_represented_correctly_in_pdf(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'      => $this->serviceA->id,
                    'description'     => 'Discounted Taxed Service',
                    'quantity'        => 1,
                    'unit_price'      => 100.00,
                    'discount_amount' => 15.00,
                    'tax_rate'        => 13.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        $this->assertStringContainsString('15.00', $pdfText); // discount
        $this->assertStringContainsString('13%', $pdfText); // tax rate
        $this->assertStringContainsString('11.05', $pdfText); // tax amount
        $this->assertStringContainsString('96.05', $pdfText); // line total
    }

    /** Test 9 — Standalone quotation */
    public function test_standalone_quotation_without_appointment_renders_pdf_successfully(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'      => $this->client->id,
            'staff_id'       => $this->staffA->id,
            'appointment_id' => null,
            'issued_date'    => '2026-10-06',
            'valid_until'    => '2026-11-05',
            'items'          => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Standalone Quotation Item',
                    'quantity'    => 1,
                    'unit_price'  => 75.00,
                ],
            ],
        ]);

        $this->assertNull($quotation->appointment_id);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $pdfText = $this->extractPdfText($response->getContent());
        $this->assertStringContainsString('Standalone Quotation', $pdfText);
        $this->assertStringNotContainsString('Appointment Date', $pdfText);
    }

    /** Test 10 — Authorization */
    public function test_authorization_enforces_location_boundaries_for_pdf_download(): void
    {
        // Quotation created for staffB at locationB
        $quotationB = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffB->id,
            'created_by'  => $this->staffB->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceB->id,
                    'quantity'    => 1,
                    'unit_price'  => 80.00,
                ],
            ],
        ]);

        // StaffA belongs strictly to locationA -> 403 Forbidden
        $responseUnauthorized = $this->actingAs($this->staffA, 'staff')
            ->get(route('quotations.download', $quotationB));

        $responseUnauthorized->assertStatus(403);

        // Admin retains global access -> 200 OK
        $responseAdmin = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotationB));

        $responseAdmin->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $responseAdmin->getContent());

        // StaffB belongs to locationB -> 200 OK
        $responseAuthorized = $this->actingAs($this->staffB, 'staff')
            ->get(route('quotations.download', $quotationB));

        $responseAuthorized->assertStatus(200);
        $this->assertStringStartsWith('%PDF', $responseAuthorized->getContent());
    }

    /** Test 11 — No payment leakage */
    public function test_no_payment_information_leakage_in_pdf(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        $this->assertStringNotContainsString('Payment Records', $pdfText);
        $this->assertStringNotContainsString('Payment History', $pdfText);
        $this->assertStringNotContainsString('Amount Paid', $pdfText);
        $this->assertStringNotContainsString('Balance Due', $pdfText);
        $this->assertStringNotContainsString('Add payment', $pdfText);
        $this->assertStringNotContainsString('Payment Method', $pdfText);
    }

    /** Test 12 — No invoice leakage */
    public function test_no_invoice_information_leakage_in_pdf(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.download', $quotation));

        $response->assertStatus(200);
        $pdfText = $this->extractPdfText($response->getContent());

        $this->assertStringNotContainsString('Invoice Number', $pdfText);
        $this->assertStringNotContainsString('Invoice Status', $pdfText);
        $this->assertStringNotContainsString('Convert to Invoice', $pdfText);
        $this->assertStringNotContainsString('invoices.download', $pdfText);
    }

    /** Test 13 — Existing quotation Show regression */
    public function test_existing_quotation_show_remains_functional(): void
    {
        $quotation = $this->quotationService->createQuotation([
            'client_id'   => $this->client->id,
            'staff_id'    => $this->staffA->id,
            'issued_date' => '2026-10-06',
            'valid_until' => '2026-11-05',
            'items'       => [
                [
                    'service_id'  => $this->serviceA->id,
                    'description' => 'Show Regression Item',
                    'quantity'    => 1,
                    'unit_price'  => 50.00,
                ],
            ],
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.show', $quotation));

        $response->assertStatus(200);
        $response->assertViewIs('quotations.show');
        $response->assertSee('Show Regression Item');
        $response->assertSee('window.print()', false);
        $response->assertSee(route('quotations.download', $quotation), false);
    }
}

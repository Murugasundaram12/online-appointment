<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\InsuranceCompany;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Package;
use App\Models\PaymentRecord;
use App\Models\Payroll;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilterAndSearchAuditTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $adminStaff;
    private Staff $receptionistStaff;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Center',
            'timezone' => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Super Admin',
            'email' => 'audit_admin@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->receptionistStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Sarah Receptionist',
            'email' => 'sarah@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'receptionist',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'location_id' => $this->location->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '4165550199',
            'city' => 'Toronto',
        ]);
    }

    public function test_staff_search_and_filter_and_pagination_preservation(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('staff.index', [
                'search' => 'Super',
                'access_level' => 'admin',
                'page' => 1,
            ]));

        $response->assertStatus(200);
        $response->assertSee('Super Admin');
        $response->assertDontSee('Sarah Receptionist');

        $staffPaginator = $response->viewData('staffs');
        $this->assertNotNull($staffPaginator);
    }

    public function test_clients_search_and_clear(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('clients.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee('Clear search');

        // Exclude test
        $emptyResponse = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('clients.index', ['search' => 'NonExistentXYZ999']));
        $emptyResponse->assertStatus(200);
        $emptyResponse->assertDontSee('john.doe@example.com');
    }

    public function test_services_search_and_category_filter(): void
    {
        $catA = ServiceCategory::create(['name' => 'Physiotherapy', 'description' => 'Physio services']);
        $catB = ServiceCategory::create(['name' => 'Massage', 'description' => 'Massage therapy']);

        $service1 = Service::create([
            'service_category_id' => $catA->id,
            'name' => 'Initial Assessment Physio',
            'duration_minutes' => 60,
            'price' => 120.00,
            'is_active' => true,
        ]);

        $service2 = Service::create([
            'service_category_id' => $catB->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 45,
            'price' => 90.00,
            'is_active' => true,
        ]);

        // Filter by category A
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('services.index', ['category' => $catA->id]));

        $response->assertStatus(200);
        $response->assertSee('Initial Assessment Physio');
        $response->assertDontSee('Deep Tissue Massage');

        // Multiple filters: search + category
        $responseMulti = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('services.index', [
                'search' => 'Initial',
                'category' => $catA->id,
            ]));
        $responseMulti->assertStatus(200);
        $responseMulti->assertSee('Initial Assessment Physio');

        $servicesPaginator = $responseMulti->viewData('services');
        $this->assertNotNull($servicesPaginator);
    }

    public function test_categories_search_and_clear(): void
    {
        $cat = ServiceCategory::create(['name' => 'Acupuncture Care', 'description' => 'Needling']);
        ServiceCategory::create(['name' => 'Chiropractic', 'description' => 'Spinal adjustments']);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('categories.index', ['search' => 'Acupuncture']));

        $response->assertStatus(200);
        $response->assertSee('Acupuncture Care');
        $response->assertDontSee('Chiropractic');
        $response->assertSee('Clear search');
    }

    public function test_invoices_search_by_invoice_number_and_status_filter(): void
    {
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-AUDIT-001',
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 0.00,
            'status' => 'issued',
        ]);

        $inv2 = Invoice::create([
            'invoice_number' => 'INV-AUDIT-002',
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'issued_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 200.00,
            'paid_amount' => 200.00,
            'status' => 'paid',
        ]);

        // Search invoice number
        $responseSearch = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('invoices.index', ['search' => 'INV-AUDIT-001']));

        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('INV-AUDIT-001');
        $responseSearch->assertDontSee('INV-AUDIT-002');

        // Status filter
        $responseStatus = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('invoices.index', ['status' => 'paid']));

        $responseStatus->assertStatus(200);
        $responseStatus->assertSee('INV-AUDIT-002');
        $responseStatus->assertDontSee('INV-AUDIT-001');

        // Multi filter (search + status)
        $responseMulti = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('invoices.index', [
                'search' => 'INV-AUDIT-001',
                'status' => 'issued',
            ]));
        $responseMulti->assertStatus(200);
        $responseMulti->assertSee('INV-AUDIT-001');
    }

    public function test_payment_records_search_and_method_filter(): void
    {
        $inv = Invoice::create([
            'invoice_number' => 'INV-PAY-001',
            'client_id' => $this->client->id,
            'staff_id' => $this->adminStaff->id,
            'issued_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'status' => 'paid',
        ]);

        $pay1 = PaymentRecord::create([
            'invoice_id' => $inv->id,
            'payment_date' => now()->toDateString(),
            'amount' => 50.00,
            'payment_method' => 'cash',
            'transaction_reference' => 'TXN-CASH-777',
        ]);

        $pay2 = PaymentRecord::create([
            'invoice_id' => $inv->id,
            'payment_date' => now()->toDateString(),
            'amount' => 50.00,
            'payment_method' => 'card',
            'transaction_reference' => 'TXN-CARD-888',
        ]);

        // Search by txn reference
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('payment-records.index', ['search' => 'TXN-CASH-777']));

        $response->assertStatus(200);
        $response->assertSee('TXN-CASH-777');
        $response->assertDontSee('TXN-CARD-888');

        // Filter by method
        $responseMethod = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('payment-records.index', ['method' => 'card']));

        $responseMethod->assertStatus(200);
        $responseMethod->assertSee('TXN-CARD-888');
        $responseMethod->assertDontSee('TXN-CASH-777');
    }

    public function test_insurance_companies_search_and_clear(): void
    {
        InsuranceCompany::create(['name' => 'Sun Life Financial']);
        InsuranceCompany::create(['name' => 'Canada Life']);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('insurance-companies.index', ['search' => 'Sun Life']));

        $response->assertStatus(200);
        $response->assertSee('Sun Life Financial');
        $response->assertDontSee('Canada Life');
        $response->assertSee('Clear search');
    }

    public function test_payroll_multi_filter_and_clear(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('payroll.index', [
                'status' => 'draft',
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Clear');
    }

    public function test_schedule_filters_and_reset(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('schedule.index', [
                'range' => 'today',
                'status' => 'working',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Reset');
    }

    public function test_reports_filter_and_reset(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->get(route('reports.appointments', [
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'client_name' => 'John',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Reset');
    }
}

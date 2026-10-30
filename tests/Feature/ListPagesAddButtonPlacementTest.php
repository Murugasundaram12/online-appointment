<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ListPagesAddButtonPlacementTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name'      => 'Main Clinic',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->location->id,
            'name'         => 'System Admin',
            'email'        => 'admin@test.org',
            'password'     => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);
    }

    public function test_clients_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('clients.index'));
        $response->assertStatus(200);
        $response->assertSee('Add New Client');
        $response->assertSee('data-bs-target="#addClientModal"', false);
    }

    public function test_services_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('services.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Service');
        $response->assertSee('data-bs-target="#addServiceModal"', false);
    }

    public function test_staff_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('staff.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Staff');
        $response->assertSee('data-bs-target="#addStaffModal"', false);
    }

    public function test_invoices_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('invoices.index'));
        $response->assertStatus(200);
        $response->assertSee('Create Invoice');
        $response->assertSee(route('invoices.create'));
    }

    public function test_categories_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('categories.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Category');
        $response->assertSee(route('categories.create'));
    }

    public function test_locations_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('locations.index'));
        $response->assertStatus(200);
        $response->assertSee('Add New Location');
        $response->assertSee(route('locations.create'));
    }

    public function test_packages_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('packages.index'));
        $response->assertStatus(200);
        $response->assertSee('Add New Package');
        $response->assertSee(route('packages.create'));
    }

    public function test_insurance_companies_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('insurance-companies.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Insurance Company');
        $response->assertSee(route('insurance-companies.create'));
    }

    public function test_forms_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('forms.index'));
        $response->assertStatus(200);
        $response->assertSee('Create Form');
        $response->assertSee(route('forms.create'));
    }

    public function test_form_records_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('form-records.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Record');
        $response->assertSee(route('form-records.create'));
    }

    public function test_payroll_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('payroll.index'));
        $response->assertStatus(200);
        $response->assertSee('Create Payroll');
        $response->assertSee(route('payroll.create'));
    }

    public function test_quotations_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Quotation');
        $response->assertSee(route('quotations.create'));
    }

    public function test_payment_records_index_renders_add_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('payment-records.index'));
        $response->assertStatus(200);
        $response->assertSee('Add Payment');
        $response->assertSee('id="btn-show-add-payment"', false);
    }
}

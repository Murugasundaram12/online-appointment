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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationIndexUiTest extends TestCase
{
    use RefreshDatabase;

    private Location $locationA;
    private Location $locationB;
    private Staff $admin;
    private Staff $practitionerA;
    private Staff $practitionerB;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationA = Location::create([
            'name'      => 'Location Alpha',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->locationB = Location::create([
            'name'      => 'Location Beta',
            'timezone'  => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'System Admin',
            'email'        => 'admin@test.org',
            'password'     => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active'    => true,
        ]);

        $this->practitionerA = Staff::create([
            'location_id'  => $this->locationA->id,
            'name'         => 'Dr. Alice',
            'email'        => 'alice@test.org',
            'password'     => Hash::make('secret123'),
            'access_level' => 'practitioner',
            'is_active'    => true,
        ]);

        $this->practitionerB = Staff::create([
            'location_id'  => $this->locationB->id,
            'name'         => 'Dr. Bob',
            'email'        => 'bob@test.org',
            'password'     => Hash::make('secret123'),
            'access_level' => 'practitioner',
            'is_active'    => true,
        ]);

        $this->client = Client::create([
            'name'   => 'John Doe',
            'email'  => 'johndoe@test.org',
            'phone'  => '555-123-4567',
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_staff_redirected_from_quotations_index(): void
    {
        $response = $this->get(route('quotations.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_sidebar_contains_quotations_index_link(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.index'));
        $response->assertStatus(200);

        // Sidebar link href points to quotations.index
        $response->assertSee(route('quotations.index'));
        // Active class on sidebar link for quotations
        $response->assertSee('active');
    }

    public function test_sidebar_is_active_on_quotations_create_page(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.create'));
        $response->assertStatus(200);
        $response->assertSee(route('quotations.index'));
    }

    public function test_quotations_index_displays_add_quotation_button(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.index'));
        $response->assertStatus(200);

        // Clearly visible single Add Quotation button
        $response->assertSee('Add Quotation');
        $response->assertSee(route('quotations.create'));

        // Assert exactly one Add Quotation button / link to quotations.create exists
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, route('quotations.create')));
        $this->assertEquals(1, substr_count($content, 'Add Quotation'));
    }

    public function test_add_quotation_button_opens_create_page(): void
    {
        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.create'));
        $response->assertStatus(200);
        $response->assertSee('Create Quotation');
        $response->assertSee('Back to quotations');
    }

    public function test_quotations_index_lists_existing_quotations(): void
    {
        $quo = Quotation::create([
            'quotation_number' => 'QUO-2026-TEST1',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->practitionerA->id,
            'created_by'       => $this->admin->id,
            'status'           => 'draft',
            'issued_date'      => Carbon::now()->toDateString(),
            'valid_until'      => Carbon::now()->addDays(30)->toDateString(),
            'subtotal'         => 150.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 19.50,
            'total_amount'     => 169.50,
        ]);

        $response = $this->actingAs($this->admin, 'staff')->get(route('quotations.index'));
        $response->assertStatus(200);
        $response->assertSee('QUO-2026-TEST1');
        $response->assertSee('John Doe');
        $response->assertSee('Dr. Alice');
        $response->assertSee(route('quotations.show', $quo->id));
        $response->assertSee(route('quotations.download', $quo->id));
    }

    public function test_location_scoping_on_quotations_index(): void
    {
        $quoA = Quotation::create([
            'quotation_number' => 'QUO-LOC-A',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->practitionerA->id,
            'status'           => 'draft',
            'issued_date'      => Carbon::now()->toDateString(),
            'valid_until'      => Carbon::now()->addDays(30)->toDateString(),
            'subtotal'         => 100.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 100.00,
        ]);

        $quoB = Quotation::create([
            'quotation_number' => 'QUO-LOC-B',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->practitionerB->id,
            'status'           => 'draft',
            'issued_date'      => Carbon::now()->toDateString(),
            'valid_until'      => Carbon::now()->addDays(30)->toDateString(),
            'subtotal'         => 200.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 200.00,
        ]);

        // Practitioner A should only see Quotation A
        $responseA = $this->actingAs($this->practitionerA, 'staff')->get(route('quotations.index'));
        $responseA->assertStatus(200);
        $responseA->assertSee('QUO-LOC-A');
        $responseA->assertDontSee('QUO-LOC-B');

        // Admin should see both
        $responseAdmin = $this->actingAs($this->admin, 'staff')->get(route('quotations.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('QUO-LOC-A');
        $responseAdmin->assertSee('QUO-LOC-B');
    }

    public function test_search_and_filter_on_quotations_index(): void
    {
        $quoDraft = Quotation::create([
            'quotation_number' => 'QUO-ALPHA-DRAFT',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->admin->id,
            'status'           => 'draft',
            'issued_date'      => Carbon::now()->toDateString(),
            'valid_until'      => Carbon::now()->addDays(30)->toDateString(),
            'subtotal'         => 100.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 100.00,
        ]);

        $quoAccepted = Quotation::create([
            'quotation_number' => 'QUO-BETA-ACCEPTED',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->admin->id,
            'status'           => 'accepted',
            'issued_date'      => Carbon::now()->toDateString(),
            'valid_until'      => Carbon::now()->addDays(30)->toDateString(),
            'subtotal'         => 200.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 200.00,
        ]);

        // Filter by status=accepted
        $responseFilter = $this->actingAs($this->admin, 'staff')->get(route('quotations.index', ['status' => 'accepted']));
        $responseFilter->assertStatus(200);
        $responseFilter->assertSee('QUO-BETA-ACCEPTED');
        $responseFilter->assertDontSee('QUO-ALPHA-DRAFT');

        // Search by query
        $responseSearch = $this->actingAs($this->admin, 'staff')->get(route('quotations.index', ['search' => 'ALPHA']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('QUO-ALPHA-DRAFT');
        $responseSearch->assertDontSee('QUO-BETA-ACCEPTED');
    }
}


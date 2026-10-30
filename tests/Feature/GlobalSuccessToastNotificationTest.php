<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Location;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalSuccessToastNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Location $location;
    private Client $client;

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

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
            'email'      => 'alice@test.org',
            'phone'      => '555-0101',
            'status'     => 'active',
        ]);
    }

    public function test_successful_redirect_renders_global_toast_and_no_inline_alert(): void
    {
        // Business settings update redirects with session('success')
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('business-settings.index'))
            ->put(route('business-settings.update'), [
                'business_name'              => 'Updated Clinic Name',
                'currency'                   => '$',
                'timezone'                   => 'America/Toronto',
                'date_format'                => 'Y-m-d',
                'time_format'                => 'H:i',
                'appointment_interval'       => 15,
                'default_appointment_status' => 'booked',
                'invoice_prefix'             => 'INV',
            ]);

        $response->assertRedirect(route('business-settings.index'));
        $response->assertSessionHas('success', 'Settings updated successfully.');

        // Follow redirect to index page
        $followUp = $this->actingAs($this->admin, 'staff')
            ->withSession(['success' => 'Settings updated successfully.'])
            ->get(route('business-settings.index'));

        $followUp->assertStatus(200);

        // 1. Toast container exists in layout
        $followUp->assertSee('app-toast-container');

        // 2. Global toast bridge attributes are present with the success message
        $followUp->assertSee('data-app-alert-type="success"', false);
        $followUp->assertSee('data-app-alert-title="Success"', false);
        $followUp->assertSee('Settings updated successfully.');

        // 3. No inline Bootstrap alert-success is rendered anywhere on the page
        $followUp->assertDontSee('alert-success');
    }

    public function test_exactly_one_success_toast_is_rendered_per_page_load(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->withSession(['success' => 'Quotation QUO-001 has been approved.'])
            ->get(route('quotations.index'));

        $response->assertStatus(200);

        // Assert exactly one data-app-alert-type="success" attribute exists
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'data-app-alert-type="success"'));

        // Assert no inline alert-success
        $response->assertDontSee('alert-success');
    }

    public function test_page_without_success_message_does_not_render_success_toast(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('quotations.index'));

        $response->assertStatus(200);
        $response->assertDontSee('data-app-alert-type="success"', false);
        $response->assertDontSee('alert-success');
    }

    public function test_quotation_approval_workflow_displays_toast_and_no_inline_alert(): void
    {
        $service = Service::create([
            'name'             => 'Consultation',
            'price'            => 100.00,
            'duration_minutes' => 30,
            'is_active'        => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QUO-2026-TEST',
            'client_id'        => $this->client->id,
            'staff_id'         => $this->admin->id,
            'status'           => 'draft',
            'issued_date'      => now()->toDateString(),
            'valid_until'      => now()->addDays(30)->toDateString(),
            'subtotal'         => 100.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 0.00,
            'total_amount'     => 100.00,
        ]);

        QuotationItem::create([
            'quotation_id'    => $quotation->id,
            'service_id'      => $service->id,
            'staff_id'        => $this->admin->id,
            'description'     => 'Consultation service',
            'quantity'        => 1.00,
            'unit_price'      => 100.00,
            'discount_amount' => 0.00,
            'tax_rate'        => 0.00,
            'tax_amount'      => 0.00,
            'line_total'      => 100.00,
            'sort_order'      => 1,
        ]);

        // Accept the quotation via POST
        $response = $this->actingAs($this->admin, 'staff')
            ->post(route('quotations.accept', $quotation->id));

        $response->assertRedirect(route('quotations.show', $quotation->id));
        $response->assertSessionHas('success', "Quotation {$quotation->quotation_number} has been approved.");

        // Follow redirect to quotation show page
        $followUp = $this->actingAs($this->admin, 'staff')
            ->withSession(['success' => "Quotation {$quotation->quotation_number} has been approved."])
            ->get(route('quotations.show', $quotation->id));

        $followUp->assertStatus(200);

        // Verify toast payload is present and no inline alert is present
        $followUp->assertSee('data-app-alert-type="success"', false);
        $followUp->assertSee("Quotation {$quotation->quotation_number} has been approved.");
        $followUp->assertDontSee('alert-success');
    }

    public function test_multiple_modules_consistently_omit_inline_success_alert(): void
    {
        $testMsg = 'Unique operation success message';

        $routesToTest = [
            route('clients.show', $this->client->id),
            route('categories.index'),
            route('insurance-companies.index'),
            route('payment-records.index'),
            route('payroll.index'),
            route('schedule.index'),
            route('subscription.index'),
        ];

        foreach ($routesToTest as $url) {
            $response = $this->actingAs($this->admin, 'staff')
                ->withSession(['success' => $testMsg])
                ->get($url);

            $response->assertStatus(200);
            $response->assertSee('data-app-alert-type="success"', false);
            $response->assertSee($testMsg);
            $response->assertDontSee('alert-success');

            // Exactly one success toast trigger in page
            $this->assertSame(1, substr_count($response->getContent(), 'data-app-alert-type="success"'));
        }
    }

    public function test_error_notifications_are_preserved(): void
    {
        $errorMsg = 'A critical error occurred.';

        $response = $this->actingAs($this->admin, 'staff')
            ->withSession(['error' => $errorMsg])
            ->get(route('categories.index'));

        $response->assertStatus(200);

        // Error message is preserved
        $response->assertSee($errorMsg);
        $response->assertDontSee('data-app-alert-type="success"', false);
    }
}

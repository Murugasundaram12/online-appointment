<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginRedirectAndStaffCategoryTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->staff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'category' => 'Massage Therapy',
            'is_active' => true,
        ]);
    }

    public function test_successful_login_redirects_to_calendar(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->staff, 'staff');
    }

    public function test_intended_redirect_is_preserved_when_accessing_protected_url(): void
    {
        // Attempt to access staff index without auth
        $this->get(route('staff.index'))->assertRedirect(route('login'));

        // Post login credentials
        $response = $this->post(route('login.store'), [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        // Should redirect to intended URL (staff.index)
        $response->assertRedirect(route('staff.index'));
    }

    public function test_intended_redirect_to_root_overridden_to_calendar(): void
    {
        // Visiting root '/' redirects unauthenticated user to login
        $this->get('/')->assertRedirect(route('login'));

        $response = $this->post(route('login.store'), [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('calendar.index'));
    }

    public function test_intended_redirect_to_dashboard_overridden_to_calendar(): void
    {
        // Visiting '/dashboard' redirects unauthenticated user to login
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $response = $this->post(route('login.store'), [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('calendar.index'));
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('calendar.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_route_remains_available(): void
    {
        $response = $this->actingAs($this->staff, 'staff')->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_staff_create_view_loads_dynamic_categories_and_saves_staff(): void
    {
        ServiceCategory::create(['name' => 'Acupuncture']);
        ServiceCategory::create(['name' => 'Chiropractic']);

        $response = $this->actingAs($this->staff, 'staff')->get(route('staff.create'));
        $response->assertOk();
        $response->assertSee('Select Category');
        $response->assertSee('Acupuncture');
        $response->assertSee('Chiropractic');

        // Create staff member selecting dynamic category
        $storeResponse = $this->actingAs($this->staff, 'staff')->post(route('staff.store'), [
            'name' => 'Bob New',
            'email' => 'bob.new@example.com',
            'password' => 'password123',
            'access_level' => 'staff',
            'category' => 'Acupuncture',
            'location_id' => $this->location->id,
            'is_active' => '1',
        ]);

        $storeResponse->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('staff', [
            'email' => 'bob.new@example.com',
            'category' => 'Acupuncture',
        ]);
    }

    public function test_staff_edit_view_has_existing_category_selected_and_updates_successfully(): void
    {
        ServiceCategory::create(['name' => 'Massage Therapy']);
        ServiceCategory::create(['name' => 'Physiotherapy']);

        $response = $this->actingAs($this->staff, 'staff')->get(route('staff.edit', $this->staff->id));
        $response->assertOk();
        $response->assertSee('Select Category');
        $response->assertSee('Massage Therapy');
        $response->assertSee('value="Massage Therapy" selected', false);

        // Update category to Physiotherapy
        $updateResponse = $this->actingAs($this->staff, 'staff')->put(route('staff.update', $this->staff->id), [
            'name' => 'Dr. Jane Smith',
            'email' => 'jane@example.com',
            'access_level' => 'admin',
            'category' => 'Physiotherapy',
            'location_id' => $this->location->id,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('staff', [
            'id' => $this->staff->id,
            'category' => 'Physiotherapy',
        ]);
    }

    public function test_newly_added_category_appears_in_staff_views_automatically(): void
    {
        $newCat = ServiceCategory::create(['name' => 'Naturopathy']);

        // Check staff index (which houses modals)
        $response = $this->actingAs($this->staff, 'staff')->get(route('staff.index'));
        $response->assertOk();
        $response->assertSee('Naturopathy');

        // Check staff create view
        $createResponse = $this->actingAs($this->staff, 'staff')->get(route('staff.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Naturopathy');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffLoginRoutingTest extends TestCase
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
            'name' => 'Dr. John Doe',
            'email' => 'john@mrclinicpro.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'category' => 'General Practice',
            'is_active' => true,
        ]);
    }

    public function test_named_routes_are_correctly_defined(): void
    {
        $this->assertSame(url('/'), route('home'));
        $this->assertSame(url('/login'), route('login'));
        $this->assertSame(url('/dashboard'), route('dashboard'));
    }

    public function test_root_url_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_login_url_renders_staff_login_form(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Staff Login');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_dashboard_url_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_root_url_redirects_authenticated_active_staff_to_dashboard(): void
    {
        $response = $this->actingAs($this->staff, 'staff')->get('/');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_url_renders_for_authenticated_staff(): void
    {
        $response = $this->actingAs($this->staff, 'staff')->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_staff_can_authenticate_via_post_login(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'john@mrclinicpro.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->staff, 'staff');
    }
}


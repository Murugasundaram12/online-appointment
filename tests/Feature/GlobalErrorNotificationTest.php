<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GlobalErrorNotificationTest extends TestCase
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
            'name' => 'Dr. Jane Doe',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_staff_self_delete_displays_global_error_notification(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->delete(route('staff.destroy', $this->staff->id));

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('error', 'You cannot delete your own account while you are logged in.');

        $followUp = $this->actingAs($this->staff, 'staff')->get(route('staff.index'));
        $followUp->assertStatus(200);
        $followUp->assertSee('app-toast-container');
        $followUp->assertSee('data-app-alert-type="danger"', false);
        $followUp->assertSee('data-app-alert-title="Error"', false);
        $followUp->assertSee('You cannot delete your own account while you are logged in.');
    }

    public function test_validation_error_renders_above_field_and_not_in_global_notification(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->from(route('staff.index'))
            ->post(route('staff.store'), [
                'name' => '', // required field missing
                'email' => 'invalid-email',
            ]);

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHasErrors(['email']);

        $followUp = $this->actingAs($this->staff, 'staff')->get(route('staff.index'));
        $followUp->assertStatus(200);
        // Field errors are rendered above inputs, not as duplicate global alerts
        $followUp->assertDontSee('data-app-alert-type="danger"', false);
    }

    public function test_authorization_403_renders_with_global_error_notification(): void
    {
        $receptionist = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Sam Reception',
            'email' => 'sam@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'receptionist',
            'is_active' => true,
        ]);

        // Receptionist cannot access staff index which requires admin/business_owner
        $response = $this->actingAs($receptionist, 'staff')
            ->get(route('staff.index'));

        $response->assertStatus(403);
        $response->assertSee('app-toast-container');
        $response->assertSee('data-app-alert-type="danger"', false);
        $response->assertSee('data-app-alert-title="Error"', false);
        $response->assertSee('Access Denied');
    }

    public function test_authentication_error_renders_field_error_above_email_on_login(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $followUp = $this->get(route('login'));
        $followUp->assertStatus(200);
        $html = $followUp->getContent();
        $emailPos = strpos($html, 'id="email"');
        $errorPos = strpos($html, 'Invalid credentials or inactive account.');
        $this->assertNotFalse($emailPos);
        $this->assertNotFalse($errorPos);
        $this->assertTrue($errorPos < $emailPos, 'Authentication error must appear before email input');
        $followUp->assertDontSee('data-app-alert-type="danger"', false);
    }
}

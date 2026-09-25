<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffLoginCaseSensitivityTest extends TestCase
{
    use RefreshDatabase;

    private Staff $superAdmin;
    private Staff $inactiveStaff;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        // Simulates the exact database record with uppercase N: udhayakumarN@gmail.com
        $this->superAdmin = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Udhay',
            'email' => 'udhayakumarN@gmail.com',
            'password' => Hash::make('SecretPass123!'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->inactiveStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Inactive User',
            'email' => 'inactive_staff@example.com',
            'password' => Hash::make('SecretPass123!'),
            'access_level' => 'admin',
            'is_active' => false,
        ]);
    }

    /**
     * Test exact-case email login succeeds and redirects to calendar.
     */
    public function test_exact_case_email_login_succeeds_and_redirects_to_calendar(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'udhayakumarN@gmail.com',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->superAdmin, 'staff');
        $this->assertTrue($this->superAdmin->isSuperAdmin());
    }

    /**
     * Test lowercase email login succeeds and redirects to calendar.
     */
    public function test_lowercase_email_login_succeeds_and_redirects_to_calendar(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'udhayakumarn@gmail.com',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->superAdmin, 'staff');
        $this->assertTrue($this->superAdmin->isSuperAdmin());
    }

    /**
     * Test mixed-case email login succeeds.
     */
    public function test_mixed_case_email_login_succeeds(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'UDHAYAKUMARN@GMAIL.COM',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->superAdmin, 'staff');
    }

    /**
     * Test leading/trailing spaces around email succeeds.
     */
    public function test_leading_trailing_spaces_around_email_succeeds(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => '  udhayakumarn@gmail.com  ',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('calendar.index'));
        $this->assertAuthenticatedAs($this->superAdmin, 'staff');
    }

    /**
     * Test invalid password is still rejected.
     */
    public function test_invalid_password_is_rejected(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'udhayakumarn@gmail.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors(['email' => 'Invalid credentials or inactive account.']);
        $this->assertGuest('staff');
    }

    /**
     * Test inactive account is still rejected.
     */
    public function test_inactive_account_is_rejected(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'inactive_staff@example.com',
            'password' => 'SecretPass123!',
        ]);

        $response->assertSessionHasErrors(['email' => 'Invalid credentials or inactive account.']);
        $this->assertGuest('staff');
    }

    /**
     * Test Super Admin check remains case-insensitive.
     */
    public function test_super_admin_check_remains_case_insensitive(): void
    {
        $this->assertTrue($this->superAdmin->isSuperAdmin());

        $staffVariant = new Staff(['email' => 'UDHAYAKUMARN@GMAIL.COM']);
        $this->assertTrue($staffVariant->isSuperAdmin());

        $normalStaff = new Staff(['email' => 'regular@example.com']);
        $this->assertFalse($normalStaff->isSuperAdmin());
    }
}

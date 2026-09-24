<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use App\Models\Location;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffPasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Downtown Clinic',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin Boss',
            'email' => 'admin_pwd_mgmt@example.com',
            'role' => 'admin',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => bcrypt('password123'),
            'location_id' => $this->location->id,
        ]);
    }

    /**
     * Requirement 3 & 7:
     * A. Add Staff with password empty -> should succeed and store NULL.
     */
    public function test_add_staff_with_empty_password_stores_null(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->post('/staff', [
                'name' => 'Staff Without Password',
                'email' => 'nopassword@example.com',
                'password' => '',
                'access_level' => 'staff',
                'location_id' => $this->location->id,
            ]);

        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('staff.index'));

        $staff = Staff::where('email', 'nopassword@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertNull($staff->password);
    }

    /**
     * B. Add Staff with valid password -> should succeed and hash password.
     */
    public function test_add_staff_with_valid_password_hashes_password(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->post('/staff', [
                'name' => 'Staff With Password',
                'email' => 'withpassword@example.com',
                'password' => 'secretPassword123',
                'access_level' => 'staff',
                'location_id' => $this->location->id,
            ]);

        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('staff.index'));

        $staff = Staff::where('email', 'withpassword@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertNotNull($staff->password);
        $this->assertTrue(Hash::check('secretPassword123', $staff->password));
    }

    /**
     * C. Add Staff with invalid/non-compliant non-empty password (< 8 chars) -> fails validation.
     */
    public function test_add_staff_with_short_password_fails_validation(): void
    {
        $res = $this->actingAs($this->adminStaff, 'staff')
            ->post('/staff', [
                'name' => 'Staff Short Pass',
                'email' => 'shortpass@example.com',
                'password' => 'short', // only 5 chars
                'access_level' => 'staff',
                'location_id' => $this->location->id,
            ]);

        $res->assertSessionHasErrors(['password']);
        $this->assertDatabaseMissing('staff', ['email' => 'shortpass@example.com']);
    }

    /**
     * D. Edit existing staff with blank password -> existing password remains unchanged.
     */
    public function test_edit_staff_with_blank_password_keeps_existing_password(): void
    {
        $staff = Staff::create([
            'name' => 'Existing Staff',
            'email' => 'existing@example.com',
            'password' => bcrypt('originalPassword123'),
            'access_level' => 'staff',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $originalHash = $staff->password;

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->put("/staff/{$staff->id}", [
                'name' => 'Existing Staff Renamed',
                'email' => 'existing@example.com',
                'password' => '',
                'access_level' => 'staff',
                'location_id' => $this->location->id,
            ]);

        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('staff.index'));

        $staff->refresh();
        $this->assertEquals('Existing Staff Renamed', $staff->name);
        $this->assertEquals($originalHash, $staff->password);
        $this->assertTrue(Hash::check('originalPassword123', $staff->password));
    }

    /**
     * E. Edit existing staff with new password -> password updates.
     */
    public function test_edit_staff_with_new_password_updates_password(): void
    {
        $staff = Staff::create([
            'name' => 'Staff To Update',
            'email' => 'update_pwd@example.com',
            'password' => bcrypt('oldSecret123'),
            'access_level' => 'staff',
            'is_active' => true,
            'location_id' => $this->location->id,
        ]);

        $res = $this->actingAs($this->adminStaff, 'staff')
            ->put("/staff/{$staff->id}", [
                'name' => 'Staff To Update',
                'email' => 'update_pwd@example.com',
                'password' => 'newSecret456',
                'access_level' => 'staff',
                'location_id' => $this->location->id,
            ]);

        $res->assertSessionHasNoErrors();
        $res->assertRedirect(route('staff.index'));

        $staff->refresh();
        $this->assertTrue(Hash::check('newSecret456', $staff->password));
    }

    /**
     * F. Password visibility toggle exists in views.
     */
    public function test_password_visibility_toggle_in_views(): void
    {
        // Add staff modal in staff/index
        $resIndex = $this->actingAs($this->adminStaff, 'staff')->get('/staff');
        $resIndex->assertStatus(200);
        $resIndex->assertSee('js-toggle-password-btn');
        $resIndex->assertSee('autocomplete="new-password"', false);
        $resIndex->assertDontSee('Password <span class="required-mark">*</span>', false);

        // Staff create page
        $resCreate = $this->actingAs($this->adminStaff, 'staff')->get('/staff/create');
        $resCreate->assertStatus(200);
        $resCreate->assertSee('js-toggle-password-btn');
        $resCreate->assertSee('autocomplete="new-password"', false);

        // Staff edit page
        $staff = Staff::first();
        $resEdit = $this->actingAs($this->adminStaff, 'staff')->get("/staff/{$staff->id}/edit");
        $resEdit->assertStatus(200);
        $resEdit->assertSee('js-toggle-password-btn');
        $resEdit->assertSee('Leave blank to keep current');
    }
}

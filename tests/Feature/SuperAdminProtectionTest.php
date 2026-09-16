<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Staff $superAdmin;
    private Staff $adminUser;
    private Staff $normalStaff;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        // Super Admin account with mixed casing in email to verify case-insensitivity
        $this->superAdmin = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Udhayakumar N (Super Admin)',
            'email' => 'udhayakumarN@gmail.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->adminUser = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin User',
            'email' => 'otheradmin@example.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->normalStaff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'John Assistant',
            'email' => 'john.assistant@example.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_delete_button_is_hidden_in_staff_list(): void
    {
        $response = $this->actingAs($this->adminUser, 'staff')->get(route('staff.index'));

        $response->assertOk();
        // Super Admin name is shown
        $response->assertSee($this->superAdmin->name);

        // Delete form for super admin is NOT rendered
        $response->assertDontSee('id="delete-staff-form-' . $this->superAdmin->id . '"', false);

        // Delete form for normal staff IS rendered
        $response->assertSee('id="delete-staff-form-' . $this->normalStaff->id . '"', false);
    }

    public function test_direct_delete_request_on_super_admin_is_rejected(): void
    {
        $response = $this->actingAs($this->adminUser, 'staff')
            ->delete(route('staff.destroy', $this->superAdmin->id));

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('error', 'Super Admin account cannot be deleted.');

        // Verify Super Admin remains in database
        $this->assertDatabaseHas('staff', [
            'id' => $this->superAdmin->id,
            'email' => 'udhayakumarN@gmail.com',
        ]);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->superAdmin, 'staff')
            ->delete(route('staff.destroy', $this->superAdmin->id));

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('error', 'Super Admin account cannot be deleted.');

        $this->assertDatabaseHas('staff', [
            'id' => $this->superAdmin->id,
        ]);
    }

    public function test_direct_delete_json_request_on_super_admin_returns_422(): void
    {
        $response = $this->actingAs($this->adminUser, 'staff')
            ->deleteJson(route('staff.destroy', $this->superAdmin->id));

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Super Admin account cannot be deleted.',
        ]);

        $this->assertDatabaseHas('staff', [
            'id' => $this->superAdmin->id,
        ]);
    }

    public function test_normal_staff_deletion_still_works(): void
    {
        $response = $this->actingAs($this->adminUser, 'staff')
            ->delete(route('staff.destroy', $this->normalStaff->id));

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('success', 'Staff deleted successfully.');

        $this->assertDatabaseMissing('staff', [
            'id' => $this->normalStaff->id,
        ]);
    }

    public function test_existing_self_delete_protection_still_works_for_other_staff(): void
    {
        $response = $this->actingAs($this->adminUser, 'staff')
            ->delete(route('staff.destroy', $this->adminUser->id));

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('error', 'You cannot delete your own account while you are logged in.');

        $this->assertDatabaseHas('staff', [
            'id' => $this->adminUser->id,
        ]);
    }
}

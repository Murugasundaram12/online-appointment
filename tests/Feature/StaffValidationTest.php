<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Staff;
use App\Models\Location;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $adminStaff;
    protected Location $location;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'City Center Clinic',
            'is_active' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Physiotherapy',
            'is_active' => true,
        ]);

        $this->adminStaff = Staff::create([
            'name' => 'Admin Boss',
            'email' => 'admin_validation@example.com',
            'access_level' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
            'location_id' => $this->location->id,
        ]);
    }

    /**
     * Staff Add - 1: Empty form except email -> succeeds.
     */
    public function test_staff_add_empty_form_except_email_succeeds(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'email' => 'only_email@example.com',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('staff', [
            'email' => 'only_email@example.com',
            'name' => null,
            'phone' => null,
            'password' => null,
        ]);
    }

    /**
     * Staff Add - 2: Email empty -> validation fails.
     */
    public function test_staff_add_email_empty_fails_validation(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'name' => 'John Doe',
                'email' => '',
                'phone' => '1234567890',
            ]);

        $response->assertSessionHasErrors(['email' => 'Email is required.']);
        $this->assertDatabaseMissing('staff', ['name' => 'John Doe']);
    }

    /**
     * Staff Add - 3: Invalid email -> validation fails.
     */
    public function test_staff_add_invalid_email_fails_validation(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'email' => 'not-an-email-address',
            ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('staff', ['email' => 'not-an-email-address']);
    }

    /**
     * Staff Add - 4: Duplicate email -> validation fails.
     */
    public function test_staff_add_duplicate_email_fails_validation(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'email' => $this->adminStaff->email,
            ]);

        $response->assertSessionHasErrors(['email' => 'This email is already used by another staff member.']);
    }

    /**
     * Staff Add - 5: Optional fields empty -> succeeds.
     */
    public function test_staff_add_optional_fields_empty_succeeds(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'name' => '',
                'email' => 'optional_empty@example.com',
                'phone' => '',
                'bio' => '',
                'color' => '',
                'registration_number' => '',
                'designation' => '',
                'category' => '',
                'location_id' => '',
                'salary' => '',
                'password' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $staff = Staff::where('email', 'optional_empty@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertNull($staff->name);
        $this->assertNull($staff->phone);
        $this->assertNull($staff->bio);
        $this->assertNull($staff->password);
    }

    /**
     * Staff Add - 6: Password supplied -> password is hashed.
     */
    public function test_staff_add_password_supplied_is_hashed(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'email' => 'pwd_supplied@example.com',
                'password' => 'secretPassword123',
            ]);

        $response->assertSessionHasNoErrors();
        $staff = Staff::where('email', 'pwd_supplied@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertNotNull($staff->password);
        $this->assertTrue(Hash::check('secretPassword123', $staff->password));
    }

    /**
     * Staff Add - 7: Password empty -> password remains NULL.
     */
    public function test_staff_add_password_empty_remains_null(): void
    {
        $response = $this->actingAs($this->adminStaff, 'staff')
            ->post(route('staff.store'), [
                'email' => 'pwd_empty@example.com',
                'password' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $staff = Staff::where('email', 'pwd_empty@example.com')->first();
        $this->assertNotNull($staff);
        $this->assertNull($staff->password);
    }

    /**
     * Staff Edit - 1: Email required.
     */
    public function test_staff_edit_email_required(): void
    {
        $staff = Staff::create([
            'name' => 'Alice Worker',
            'email' => 'alice@example.com',
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Alice Worker',
                'email' => '',
            ]);

        $response->assertSessionHasErrors(['email' => 'Email is required.']);
        $this->assertEquals('alice@example.com', $staff->fresh()->email);
    }

    /**
     * Staff Edit - 2: Optional fields can be cleared.
     */
    public function test_staff_edit_optional_fields_can_be_cleared(): void
    {
        $staff = Staff::create([
            'name' => 'Bob Full Details',
            'email' => 'bob@example.com',
            'phone' => '555-123-4567',
            'bio' => 'Some bio notes',
            'registration_number' => 'REG-999',
            'designation' => 'Practitioner',
            'category' => 'Physiotherapy',
            'location_id' => $this->location->id,
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => '',
                'email' => 'bob@example.com',
                'phone' => '',
                'bio' => '',
                'registration_number' => '',
                'designation' => '',
                'category' => '',
                'location_id' => '',
                'password' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $updated = $staff->fresh();
        $this->assertNull($updated->name);
        $this->assertNull($updated->phone);
        $this->assertNull($updated->bio);
        $this->assertNull($updated->registration_number);
        $this->assertNull($updated->designation);
        $this->assertNull($updated->category);
        $this->assertNull($updated->location_id);
    }

    /**
     * Staff Edit - 3: Empty password keeps existing password.
     */
    public function test_staff_edit_empty_password_keeps_existing_password(): void
    {
        $staff = Staff::create([
            'name' => 'Charlie Guarded',
            'email' => 'charlie@example.com',
            'password' => Hash::make('originalPassword789'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $originalHash = $staff->password;

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Charlie Guarded',
                'email' => 'charlie@example.com',
                'password' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $updated = $staff->fresh();
        $this->assertEquals($originalHash, $updated->password);
        $this->assertTrue(Hash::check('originalPassword789', $updated->password));
    }

    /**
     * Staff Edit - 4: New password updates password.
     */
    public function test_staff_edit_new_password_updates_password(): void
    {
        $staff = Staff::create([
            'name' => 'Dana Changing',
            'email' => 'dana@example.com',
            'password' => Hash::make('oldPass123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Dana Changing',
                'email' => 'dana@example.com',
                'password' => 'newSecretPass999',
            ]);

        $response->assertSessionHasNoErrors();
        $updated = $staff->fresh();
        $this->assertTrue(Hash::check('newSecretPass999', $updated->password));
        $this->assertFalse(Hash::check('oldPass123', $updated->password));
    }

    /**
     * Staff Edit - 5: Duplicate email is rejected.
     */
    public function test_staff_edit_duplicate_email_is_rejected(): void
    {
        $staff1 = Staff::create([
            'name' => 'Staff One',
            'email' => 'staff1@example.com',
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $staff2 = Staff::create([
            'name' => 'Staff Two',
            'email' => 'staff2@example.com',
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff2->id), [
                'email' => 'staff1@example.com',
            ]);

        $response->assertSessionHasErrors(['email' => 'This email is already used by another staff member.']);
        $this->assertEquals('staff2@example.com', $staff2->fresh()->email);
    }

    /**
     * Staff Edit - 6: Same existing email is allowed.
     */
    public function test_staff_edit_same_existing_email_is_allowed(): void
    {
        $staff = Staff::create([
            'name' => 'Eve Unchanged Email',
            'email' => 'eve@example.com',
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminStaff, 'staff')
            ->put(route('staff.update', $staff->id), [
                'name' => 'Eve Modified Name',
                'email' => 'eve@example.com',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));
        $this->assertEquals('Eve Modified Name', $staff->fresh()->name);
        $this->assertEquals('eve@example.com', $staff->fresh()->email);
    }

    /**
     * Frontend checks: only Email has required mark and required attribute.
     */
    public function test_frontend_only_email_has_required_mark_and_attribute(): void
    {
        // 1. Create page
        $resCreate = $this->actingAs($this->adminStaff, 'staff')->get(route('staff.create'));
        $resCreate->assertStatus(200);
        $createHtml = $resCreate->getContent();

        $this->assertStringContainsString('Email <span class="required-mark">*</span>', $createHtml);
        $this->assertStringNotContainsString('Full Name <span class="required-mark">*</span>', $createHtml);
        $this->assertStringNotContainsString('id="name" name="name" value="" required', $createHtml);

        // 2. Edit page
        $resEdit = $this->actingAs($this->adminStaff, 'staff')->get(route('staff.edit', $this->adminStaff->id));
        $resEdit->assertStatus(200);
        $editHtml = $resEdit->getContent();

        $this->assertStringContainsString('Email <span class="required-mark">*</span>', $editHtml);
        $this->assertStringNotContainsString('Full Name <span class="required-mark">*</span>', $editHtml);

        // 3. Index page (modals)
        $resIndex = $this->actingAs($this->adminStaff, 'staff')->get(route('staff.index'));
        $resIndex->assertStatus(200);
        $indexHtml = $resIndex->getContent();

        $this->assertStringNotContainsString('Staff name <span class="required-mark">*</span>', $indexHtml);
    }
}

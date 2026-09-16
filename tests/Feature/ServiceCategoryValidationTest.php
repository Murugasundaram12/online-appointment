<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceCategoryValidationTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private ServiceCategory $categoryA;
    private ServiceCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->staff = Staff::create([
            'location_id' => $location->id,
            'name' => 'Dr. Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => Hash::make('secret123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->categoryA = ServiceCategory::create([
            'name' => 'Massage Therapy',
            'description' => 'Therapeutic massage treatments',
        ]);

        $this->categoryB = ServiceCategory::create([
            'name' => 'Physiotherapy',
            'description' => 'Physical rehabilitation services',
        ]);
    }

    public function test_add_service_view_shows_required_category_dropdown(): void
    {
        $response = $this->actingAs($this->staff, 'staff')->get(route('services.create'));

        $response->assertOk();
        $response->assertSee('Select Category');
        $response->assertSee('Massage Therapy');
        $response->assertSee('Physiotherapy');
        $response->assertSee('name="service_category_id"', false);
        $response->assertSee('required', false);
    }

    public function test_add_service_with_valid_category_succeeds(): void
    {
        $response = $this->actingAs($this->staff, 'staff')->post(route('services.store'), [
            'name' => 'Deep Tissue Massage 60m',
            'service_category_id' => $this->categoryA->id,
            'type' => 'in_person',
            'price' => 120.00,
            'duration_minutes' => 60,
            'buffer_minutes' => 10,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('services.index'));
        $response->assertSessionHas('success', 'Service created successfully.');

        $this->assertDatabaseHas('services', [
            'name' => 'Deep Tissue Massage 60m',
            'service_category_id' => $this->categoryA->id,
            'price' => 120.00,
        ]);
    }

    public function test_add_service_without_category_fails_validation(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Service Without Category',
                'service_category_id' => '',
                'type' => 'in_person',
                'price' => 90.00,
                'duration_minutes' => 45,
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors(['service_category_id']);
        $this->assertEquals(
            'The category field is required.',
            session('errors')->first('service_category_id')
        );

        $this->assertDatabaseMissing('services', [
            'name' => 'Service Without Category',
        ]);
    }

    public function test_add_service_with_invalid_category_fails_validation(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => 'Invalid Category Service',
                'service_category_id' => 999999, // Non-existent category
                'type' => 'in_person',
                'price' => 90.00,
                'duration_minutes' => 45,
            ]);

        $response->assertRedirect(route('services.create'));
        $response->assertSessionHasErrors(['service_category_id']);
        $this->assertEquals(
            'The selected category is invalid.',
            session('errors')->first('service_category_id')
        );
    }

    public function test_edit_service_view_keeps_existing_category_selected(): void
    {
        $service = Service::create([
            'service_category_id' => $this->categoryA->id,
            'name' => 'Swedish Massage',
            'price' => 100,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff, 'staff')->get(route('services.edit', $service->id));

        $response->assertOk();
        $response->assertSee('value="' . $this->categoryA->id . '" selected', false);
    }

    public function test_edit_service_keeping_existing_category_succeeds(): void
    {
        $service = Service::create([
            'service_category_id' => $this->categoryA->id,
            'name' => 'Swedish Massage',
            'price' => 100,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff, 'staff')->put(route('services.update', $service->id), [
            'name' => 'Swedish Massage Updated',
            'service_category_id' => $this->categoryA->id,
            'type' => 'in_person',
            'price' => 110.00,
            'duration_minutes' => 60,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('services.index'));
        $response->assertSessionHas('success', 'Service updated successfully.');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Swedish Massage Updated',
            'service_category_id' => $this->categoryA->id,
            'price' => 110.00,
        ]);
    }

    public function test_edit_service_changing_category_succeeds(): void
    {
        $service = Service::create([
            'service_category_id' => $this->categoryA->id,
            'name' => 'Acupuncture Combo',
            'price' => 150,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff, 'staff')->put(route('services.update', $service->id), [
            'name' => 'Acupuncture Combo',
            'service_category_id' => $this->categoryB->id,
            'type' => 'in_person',
            'price' => 150.00,
            'duration_minutes' => 60,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'service_category_id' => $this->categoryB->id,
        ]);
    }

    public function test_edit_service_clearing_category_fails_validation(): void
    {
        $service = Service::create([
            'service_category_id' => $this->categoryA->id,
            'name' => 'Specialized Therapy',
            'price' => 130,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff, 'staff')
            ->from(route('services.edit', $service->id))
            ->put(route('services.update', $service->id), [
                'name' => 'Specialized Therapy',
                'service_category_id' => '', // Cleared category
                'type' => 'in_person',
                'price' => 130.00,
                'duration_minutes' => 45,
            ]);

        $response->assertRedirect(route('services.edit', $service->id));
        $response->assertSessionHasErrors(['service_category_id']);
        $this->assertEquals(
            'The category field is required.',
            session('errors')->first('service_category_id')
        );

        // Verify database value remains unchanged
        $service->refresh();
        $this->assertEquals($this->categoryA->id, $service->service_category_id);
    }
}

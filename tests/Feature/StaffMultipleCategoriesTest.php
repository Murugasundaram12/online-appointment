<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\StaffSchedule;
use App\Support\StaffCategoryService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffMultipleCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Location $location;
    private ServiceCategory $dentalCategory;
    private ServiceCategory $cardioCategory;
    private ServiceCategory $physioCategory;
    private Service $dentalService;
    private Service $cardioService;
    private Service $physioService;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = Location::create([
            'name' => 'Main Health Center',
            'timezone' => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'name' => 'Admin Boss',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->dentalCategory = ServiceCategory::create(['name' => 'Dental']);
        $this->cardioCategory = ServiceCategory::create(['name' => 'Cardiology']);
        $this->physioCategory = ServiceCategory::create(['name' => 'Physiotherapy']);

        $this->dentalService = Service::create([
            'name' => 'Dental Cleaning',
            'service_category_id' => $this->dentalCategory->id,
            'price' => 100,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->cardioService = Service::create([
            'name' => 'Cardio Checkup',
            'service_category_id' => $this->cardioCategory->id,
            'price' => 200,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->physioService = Service::create([
            'name' => 'Physiotherapy Session',
            'service_category_id' => $this->physioCategory->id,
            'price' => 120,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'name' => 'Patient John',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'client_since' => now()->toDateString(),
        ]);
    }

    /**
     * Requirement 1: Staff can have multiple categories.
     */
    public function test_staff_can_have_multiple_categories(): void
    {
        $staff = Staff::create([
            'name' => 'Kumar',
            'email' => 'kumar@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $staff->categories()->attach([
            $this->dentalCategory->id,
            $this->cardioCategory->id,
            $this->physioCategory->id,
        ]);

        $this->assertCount(3, $staff->categories);
        $this->assertTrue($staff->categories->contains($this->dentalCategory));
        $this->assertTrue($staff->categories->contains($this->cardioCategory));
        $this->assertTrue($staff->categories->contains($this->physioCategory));
    }

    /**
     * Requirement 2: Staff categories are stored in staff_categories pivot table.
     */
    public function test_staff_categories_are_stored_in_staff_categories(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Multi',
            'email' => 'multi@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->dentalCategory->id,
        ]);
        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->cardioCategory->id,
        ]);
    }

    /**
     * Requirement 3: Duplicate category assignment is prevented.
     */
    public function test_duplicate_category_assignment_is_prevented(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Unique',
            'email' => 'unique@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        // 1. Controller validation rejects duplicate category IDs
        $response = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'name' => 'Duplicate Attempt',
            'email' => 'dup_controller@example.com',
            'categories' => [$this->dentalCategory->id, $this->dentalCategory->id],
        ]);
        $response->assertSessionHasErrors(['categories.0']);

        // 2. Database composite unique constraint prevents duplicate rows
        $staff->categories()->attach($this->dentalCategory->id);
        $this->expectException(QueryException::class);
        DB::table('staff_categories')->insert([
            'staff_id' => $staff->id,
            'service_category_id' => $this->dentalCategory->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Requirement 4: Staff update syncs categories correctly.
     */
    public function test_staff_update_syncs_categories_correctly(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Syncer',
            'email' => 'syncer@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $staff->categories()->attach([$this->dentalCategory->id]);

        $response = $this->actingAs($this->admin, 'staff')->put(route('staff.update', $staff->id), [
            'name' => 'Dr. Syncer',
            'email' => 'syncer@example.com',
            'categories' => [$this->cardioCategory->id, $this->physioCategory->id],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));

        $freshStaff = $staff->fresh();
        $this->assertFalse($freshStaff->categories->contains($this->dentalCategory));
        $this->assertTrue($freshStaff->categories->contains($this->cardioCategory));
        $this->assertTrue($freshStaff->categories->contains($this->physioCategory));
    }

    /**
     * Requirement 5: Removing a category removes the pivot assignment.
     */
    public function test_removing_a_category_removes_the_pivot_assignment(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Remover',
            'email' => 'remover@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        // Remove Cardiology by sending only Dental
        $response = $this->actingAs($this->admin, 'staff')->put(route('staff.update', $staff->id), [
            'name' => 'Dr. Remover',
            'email' => 'remover@example.com',
            'categories' => [$this->dentalCategory->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->dentalCategory->id,
        ]);
        $this->assertDatabaseMissing('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->cardioCategory->id,
        ]);
    }

    /**
     * Requirement 6: Adding a category adds the pivot assignment.
     */
    public function test_adding_a_category_adds_the_pivot_assignment(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Adder',
            'email' => 'adder@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $staff->categories()->attach([$this->dentalCategory->id]);

        // Add Cardiology
        $response = $this->actingAs($this->admin, 'staff')->put(route('staff.update', $staff->id), [
            'name' => 'Dr. Adder',
            'email' => 'adder@example.com',
            'categories' => [$this->dentalCategory->id, $this->cardioCategory->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->dentalCategory->id,
        ]);
        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $staff->id,
            'service_category_id' => $this->cardioCategory->id,
        ]);
    }

    /**
     * Requirement 7: Existing legacy staff.category data migrates correctly.
     */
    public function test_existing_legacy_staff_category_data_migrates_correctly(): void
    {
        $legacyStaff = Staff::create([
            'name' => 'Legacy Doctor',
            'email' => 'legacy@example.com',
            'category' => 'Dental',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $result = StaffCategoryService::migrateLegacyStaffCategories();

        $this->assertGreaterThanOrEqual(1, $result['migrated']);
        $this->assertDatabaseHas('staff_categories', [
            'staff_id' => $legacyStaff->id,
            'service_category_id' => $this->dentalCategory->id,
        ]);
        // Legacy column is preserved and not deleted or emptied
        $this->assertEquals('Dental', $legacyStaff->fresh()->category);
    }

    /**
     * Requirement 8: Unmatched legacy category values are reported, not silently lost.
     */
    public function test_unmatched_legacy_category_values_are_reported_not_silently_lost(): void
    {
        $unmatchedStaff = Staff::create([
            'name' => 'Unmatched Staff',
            'email' => 'unmatched@example.com',
            'category' => 'Unknown Specialty That Does Not Exist',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $result = StaffCategoryService::migrateLegacyStaffCategories();

        $this->assertNotEmpty($result['unmatched']);
        $matchedUnmatched = collect($result['unmatched'])->firstWhere('staff_id', $unmatchedStaff->id);
        $this->assertNotNull($matchedUnmatched);
        $this->assertEquals('Unknown Specialty That Does Not Exist', $matchedUnmatched['legacy_category']);
        // Legacy column is safely preserved
        $this->assertEquals('Unknown Specialty That Does Not Exist', $unmatchedStaff->fresh()->category);
    }

    /**
     * Requirement 9: Staff with Dental can provide Dental service.
     */
    public function test_staff_with_dental_can_provide_dental_service(): void
    {
        $staff = Staff::create([
            'name' => 'Dental Doctor',
            'email' => 'dental_doc@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staff->categories()->attach($this->dentalCategory->id);

        $this->assertTrue(StaffCategoryService::staffCanProvide($staff, $this->dentalService));
    }

    /**
     * Requirement 10: Staff with Dental + Cardiology can provide both services.
     */
    public function test_staff_with_dental_and_cardiology_can_provide_both_services(): void
    {
        $staff = Staff::create([
            'name' => 'Dental Cardio Doctor',
            'email' => 'dental_cardio@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $this->assertTrue(StaffCategoryService::staffCanProvide($staff, $this->dentalService));
        $this->assertTrue(StaffCategoryService::staffCanProvide($staff, $this->cardioService));
    }

    /**
     * Requirement 11: Staff without Cardiology cannot provide Cardiology service.
     */
    public function test_staff_without_cardiology_cannot_provide_cardiology_service(): void
    {
        $staff = Staff::create([
            'name' => 'Dental Only Doctor',
            'email' => 'dental_only@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staff->categories()->attach($this->dentalCategory->id);

        $this->assertFalse(StaffCategoryService::staffCanProvide($staff, $this->cardioService));
    }

    /**
     * Requirement 12: Staff with zero categories preserves existing unrestricted behavior.
     */
    public function test_staff_with_zero_categories_preserves_unrestricted_behavior(): void
    {
        $unrestrictedStaff = Staff::create([
            'name' => 'Unrestricted Staff',
            'email' => 'unrestricted@example.com',
            'category' => null,
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->assertTrue($unrestrictedStaff->categories->isEmpty());
        // Can provide dental, cardio, and physio
        $this->assertTrue(StaffCategoryService::staffCanProvide($unrestrictedStaff, $this->dentalService));
        $this->assertTrue(StaffCategoryService::staffCanProvide($unrestrictedStaff, $this->cardioService));
        $this->assertTrue(StaffCategoryService::staffCanProvide($unrestrictedStaff, $this->physioService));

        // A service without category is available to all staff
        $uncategorizedService = Service::create([
            'name' => 'General Consultation',
            'service_category_id' => null,
            'price' => 50,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $restrictedStaff = Staff::create([
            'name' => 'Restricted Staff',
            'email' => 'restricted@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $restrictedStaff->categories()->attach($this->dentalCategory->id);

        $this->assertTrue(StaffCategoryService::staffCanProvide($restrictedStaff, $uncategorizedService));
    }

    /**
     * Requirement 13: Calendar backend accepts matching Staff/Service category.
     */
    public function test_calendar_backend_accepts_matching_staff_service_category(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Matching',
            'email' => 'matching@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => $isoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [],
        ]);

        $start = $nextMonday->copy()->setTime(10, 0)->format('Y-m-d H:i:s');
        $end = $nextMonday->copy()->setTime(10, 30)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $staff->id,
            'service_id' => $this->dentalService->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $staff->id,
            'service_id' => $this->dentalService->id,
        ]);
    }

    /**
     * Requirement 14: Calendar backend rejects non-matching Staff/Service category.
     */
    public function test_calendar_backend_rejects_non_matching_staff_service_category(): void
    {
        $staff = Staff::create([
            'name' => 'Dental Specialist',
            'email' => 'dental_spec@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $staff->categories()->attach([$this->dentalCategory->id]);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => $isoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [],
        ]);

        $start = $nextMonday->copy()->setTime(10, 0)->format('Y-m-d H:i:s');
        $end = $nextMonday->copy()->setTime(10, 30)->format('Y-m-d H:i:s');

        // Store appointment with Cardiology service should fail
        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $staff->id,
            'service_id' => $this->cardioService->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'The selected service is not available for this staff member.',
        ]);

        // Update appointment with mismatched category should also fail
        $appointment = Appointment::create([
            'staff_id' => $staff->id,
            'service_id' => $this->dentalService->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $updateResponse = $this->actingAs($this->admin, 'staff')->putJson(route('calendar.update', $appointment->id), [
            'service_id' => $this->cardioService->id,
        ]);

        $updateResponse->assertStatus(422);
        $updateResponse->assertJson([
            'success' => false,
            'message' => 'The selected service is not available for this staff member.',
        ]);
    }

    /**
     * Requirement 15: Online booking returns only eligible Staff.
     */
    public function test_online_booking_returns_only_eligible_staff(): void
    {
        $staffDental = Staff::create([
            'name' => 'Dental Provider',
            'email' => 'dental_prov@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staffDental->categories()->attach($this->dentalCategory->id);

        $staffCardio = Staff::create([
            'name' => 'Cardio Provider',
            'email' => 'cardio_prov@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staffCardio->categories()->attach($this->cardioCategory->id);

        $staffUnrestricted = Staff::create([
            'name' => 'General Provider',
            'email' => 'gen_prov@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        foreach ([$staffDental, $staffCardio, $staffUnrestricted] as $s) {
            StaffSchedule::create([
                'staff_id' => $s->id,
                'day_of_week' => $isoDay,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_working' => true,
                'breaks' => [],
            ]);
        }

        $response = $this->getJson(route('online-booking.slots', [
            'service_id' => $this->dentalService->id,
            'date' => $nextMonday->toDateString(),
            'location_id' => $this->location->id,
        ]));

        $response->assertOk();
        $slots = $response->json();
        $returnedStaffIds = collect($slots)->pluck('staff_id')->unique()->all();

        $this->assertContains($staffDental->id, $returnedStaffIds);
        $this->assertContains($staffUnrestricted->id, $returnedStaffIds);
        $this->assertNotContains($staffCardio->id, $returnedStaffIds);
    }

    /**
     * Requirement 16: Online booking backend rejects an ineligible Staff selection.
     */
    public function test_online_booking_backend_rejects_ineligible_staff_selection(): void
    {
        $staffCardio = Staff::create([
            'name' => 'Cardio Only',
            'email' => 'cardio_only@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staffCardio->categories()->attach($this->cardioCategory->id);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $staffCardio->id,
            'day_of_week' => $isoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [],
        ]);

        $start = $nextMonday->copy()->setTime(10, 0)->format('Y-m-d H:i:s');
        $end = $nextMonday->copy()->setTime(10, 30)->format('Y-m-d H:i:s');

        $response = $this->post(route('online-booking.store'), [
            'staff_id' => $staffCardio->id,
            'service_id' => $this->dentalService->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'client_name' => 'Online Booking Patient',
            'client_email' => 'online_patient@example.com',
        ]);

        $response->assertSessionHasErrors(['staff_id']);
        $this->assertEquals(
            'The selected service is not available for this staff member.',
            session('errors')->first('staff_id')
        );
    }

    /**
     * Requirement 17: Staff category filtering works via pivot relationship.
     */
    public function test_staff_category_filtering_works(): void
    {
        $staffDental = Staff::create([
            'name' => 'Dental Filter Staff',
            'email' => 'dental_filter@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staffDental->categories()->attach($this->dentalCategory->id);

        $staffCardio = Staff::create([
            'name' => 'Cardio Filter Staff',
            'email' => 'cardio_filter@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
        ]);
        $staffCardio->categories()->attach($this->cardioCategory->id);

        // Filter by Dental
        $response = $this->actingAs($this->admin, 'staff')->get(route('staff.index', ['category' => 'Dental']));
        $response->assertOk();
        $response->assertSee('Dental Filter Staff');
        $response->assertDontSee('Cardio Filter Staff');

        // Filter by Cardiology
        $cardioResponse = $this->actingAs($this->admin, 'staff')->get(route('staff.index', ['category' => 'Cardiology']));
        $cardioResponse->assertOk();
        $cardioResponse->assertSee('Cardio Filter Staff');
        $cardioResponse->assertDontSee('Dental Filter Staff');
    }

    /**
     * Requirement 18: Existing Staff validation still works.
     */
    public function test_existing_staff_validation_still_works(): void
    {
        // 1. Only email is required
        $response = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'email' => 'only_email_req18@example.com',
        ]);
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('staff', ['email' => 'only_email_req18@example.com']);

        // 2. Missing email fails
        $failResponse = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'name' => 'No Email Person',
            'email' => '',
        ]);
        $failResponse->assertSessionHasErrors(['email' => 'Email is required.']);

        // 3. Duplicate email fails
        $dupResponse = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'email' => 'only_email_req18@example.com',
        ]);
        $dupResponse->assertSessionHasErrors(['email' => 'This email is already used by another staff member.']);

        // 4. Invalid category ID in array fails
        $invalidCatResponse = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'email' => 'invalid_cat@example.com',
            'categories' => [99999],
        ]);
        $invalidCatResponse->assertSessionHasErrors(['categories.0']);

        // 5. Non-array categories fails
        $nonArrayCatResponse = $this->actingAs($this->admin, 'staff')->post(route('staff.store'), [
            'email' => 'non_array_cat@example.com',
            'categories' => 'not-an-array',
        ]);
        $nonArrayCatResponse->assertSessionHasErrors(['categories']);
    }

    /**
     * Regression Test 1: Staff has Dental + Cardiology -> Calendar service list contains services from BOTH categories.
     */
    public function test_calendar_staff_with_dental_and_cardiology_shows_services_from_both_categories(): void
    {
        $kumar = Staff::create([
            'name' => 'Dr. Kumar',
            'email' => 'dr.kumar@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $kumar->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));
        $response->assertOk();

        $staffs = $response->viewData('staffs');
        $kumarInView = $staffs->firstWhere('id', $kumar->id);
        $this->assertNotNull($kumarInView);
        $this->assertEqualsCanonicalizing([$this->dentalCategory->id, $this->cardioCategory->id], $kumarInView->category_ids);

        // Simulate servicesForStaff logic
        $services = $response->viewData('services');
        $eligibleServices = $services->filter(function ($svc) use ($kumarInView) {
            $catId = $svc->service_category_id ?? $svc->category?->id;
            return !$catId || in_array((int) $catId, array_map('intval', $kumarInView->category_ids), true);
        })->pluck('id')->all();

        $this->assertContains($this->dentalService->id, $eligibleServices);
        $this->assertContains($this->cardioService->id, $eligibleServices);
    }

    /**
     * Regression Test 2: Staff has only Dental -> Cardiology services are NOT shown.
     */
    public function test_calendar_staff_with_only_dental_does_not_show_cardiology_services(): void
    {
        $dentalStaff = Staff::create([
            'name' => 'Dr. Dental Only',
            'email' => 'dental.only@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $dentalStaff->categories()->attach([$this->dentalCategory->id]);

        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));
        $response->assertOk();

        $staffs = $response->viewData('staffs');
        $staffInView = $staffs->firstWhere('id', $dentalStaff->id);
        $this->assertNotNull($staffInView);
        $this->assertEqualsCanonicalizing([$this->dentalCategory->id], $staffInView->category_ids);

        $services = $response->viewData('services');
        $eligibleServices = $services->filter(function ($svc) use ($staffInView) {
            $catId = $svc->service_category_id ?? $svc->category?->id;
            return !$catId || in_array((int) $catId, array_map('intval', $staffInView->category_ids), true);
        })->pluck('id')->all();

        $this->assertContains($this->dentalService->id, $eligibleServices);
        $this->assertNotContains($this->cardioService->id, $eligibleServices);
    }

    /**
     * Regression Test 3: Staff has Dental + Cardiology -> Pediatrics service is NOT shown.
     */
    public function test_calendar_staff_with_dental_and_cardiology_does_not_show_pediatrics(): void
    {
        $pediatricCategory = ServiceCategory::create(['name' => 'Pediatrics']);
        $pediatricService = Service::create([
            'name' => 'Pediatric Consultation',
            'service_category_id' => $pediatricCategory->id,
            'price' => 150,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $kumar = Staff::create([
            'name' => 'Dr. Kumar Double',
            'email' => 'dr.kumar.double@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $kumar->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));
        $response->assertOk();

        $staffs = $response->viewData('staffs');
        $kumarInView = $staffs->firstWhere('id', $kumar->id);

        $services = $response->viewData('services');
        $eligibleServices = $services->filter(function ($svc) use ($kumarInView) {
            $catId = $svc->service_category_id ?? $svc->category?->id;
            return !$catId || in_array((int) $catId, array_map('intval', $kumarInView->category_ids), true);
        })->pluck('id')->all();

        $this->assertContains($this->dentalService->id, $eligibleServices);
        $this->assertContains($this->cardioService->id, $eligibleServices);
        $this->assertNotContains($pediatricService->id, $eligibleServices);
    }

    /**
     * Regression Test 4: Staff has zero assigned categories -> Unrestricted behavior preserved.
     */
    public function test_calendar_staff_with_zero_categories_preserves_unrestricted_behavior(): void
    {
        $unrestrictedStaff = Staff::create([
            'name' => 'Dr. General Zero',
            'email' => 'general.zero@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        // zero categories attached

        $response = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));
        $response->assertOk();

        $staffs = $response->viewData('staffs');
        $staffInView = $staffs->firstWhere('id', $unrestrictedStaff->id);
        $this->assertNotNull($staffInView);
        $this->assertEmpty($staffInView->category_ids);

        $services = $response->viewData('services');
        $eligibleServices = $services->filter(function ($svc) use ($staffInView) {
            $catIds = array_map('intval', $staffInView->category_ids ?? []);
            if (empty($catIds)) {
                return true; // unrestricted
            }
            $catId = $svc->service_category_id ?? $svc->category?->id;
            return !$catId || in_array((int) $catId, $catIds, true);
        })->pluck('id')->all();

        $this->assertContains($this->dentalService->id, $eligibleServices);
        $this->assertContains($this->cardioService->id, $eligibleServices);
        $this->assertContains($this->physioService->id, $eligibleServices);
    }

    /**
     * Regression Test 5: Backend appointment creation with second assigned category succeeds.
     */
    public function test_backend_appointment_creation_with_second_category_succeeds(): void
    {
        $staff = Staff::create([
            'name' => 'Dr. Multi Kumar',
            'email' => 'multi.kumar@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => $isoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [],
        ]);

        $start = $nextMonday->copy()->setTime(11, 0)->format('Y-m-d H:i:s');
        $end = $nextMonday->copy()->setTime(11, 30)->format('Y-m-d H:i:s');

        // Create appointment with Cardiology (second assigned category)
        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $staff->id,
            'service_id' => $this->cardioService->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('appointments', [
            'staff_id' => $staff->id,
            'service_id' => $this->cardioService->id,
        ]);
    }

    /**
     * Regression Test 6: Backend appointment creation with unassigned category fails.
     */
    public function test_backend_appointment_creation_with_unassigned_category_fails(): void
    {
        $pediatricCategory = ServiceCategory::create(['name' => 'Pediatrics 2']);
        $pediatricService = Service::create([
            'name' => 'Pediatric Visit',
            'service_category_id' => $pediatricCategory->id,
            'price' => 110,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

        $staff = Staff::create([
            'name' => 'Dr. Multi Kumar 2',
            'email' => 'multi.kumar2@example.com',
            'location_id' => $this->location->id,
            'is_active' => true,
            'password' => Hash::make('secret'),
        ]);
        $staff->categories()->attach([$this->dentalCategory->id, $this->cardioCategory->id]);

        $nextMonday = Carbon::parse('next monday')->startOfDay();
        $isoDay = (string) ($nextMonday->dayOfWeekIso - 1);

        StaffSchedule::create([
            'staff_id' => $staff->id,
            'day_of_week' => $isoDay,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_working' => true,
            'breaks' => [],
        ]);

        $start = $nextMonday->copy()->setTime(11, 0)->format('Y-m-d H:i:s');
        $end = $nextMonday->copy()->setTime(11, 30)->format('Y-m-d H:i:s');

        // Create appointment with Pediatrics (unassigned category)
        $response = $this->actingAs($this->admin, 'staff')->postJson(route('calendar.store'), [
            'staff_id' => $staff->id,
            'service_id' => $pediatricService->id,
            'client_id' => $this->client->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'The selected service is not available for this staff member.',
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Staff;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ValidationErrorUiTest extends TestCase
{
    use RefreshDatabase;

    private Staff $admin;
    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        SubscriptionPlan::create([
            'name' => 'Unlimited Plan',
            'description' => 'Full access plan',
            'price' => 99.00,
            'billing_cycle' => 'monthly',
            'staff_limit' => null,
            'location_limit' => null,
            'appointment_limit' => null,
            'is_active' => true,
        ]);

        $this->location = Location::create([
            'name' => 'Main Clinic',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->admin = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_login_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        // Email error message should appear before the email input
        $emailPos = strpos($html, 'id="email"');
        $this->assertNotFalse($emailPos);
        $beforeEmail = substr($html, 0, $emailPos);
        $this->assertStringContainsString('The email field is required.', $beforeEmail);

        // Password error message should appear before the password input
        $passwordPos = strpos($html, 'id="password"');
        $this->assertNotFalse($passwordPos);
        $beforePassword = substr($html, 0, $passwordPos);
        $this->assertStringContainsString('The password field is required.', $beforePassword);
    }

    public function test_staff_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('staff.create'))
            ->post(route('staff.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'password' => 'short',
            ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        // Name error should appear before name input
        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos, 'Name error must appear before name input');

        // Email error should appear before email input
        $emailInputPos = strpos($html, 'id="email"');
        $emailErrorPos = strpos($html, 'The email field must be a valid email address.');
        $this->assertNotFalse($emailInputPos);
        $this->assertNotFalse($emailErrorPos);
        $this->assertTrue($emailErrorPos < $emailInputPos, 'Email error must appear before email input');

        // Password error should appear before password input
        $passInputPos = strpos($html, 'id="createStaffPassword"');
        $passErrorPos = strpos($html, 'The password field must be at least 8 characters.');
        $this->assertNotFalse($passInputPos);
        $this->assertNotFalse($passErrorPos);
        $this->assertTrue($passErrorPos < $passInputPos, 'Password error must appear before password input');
    }

    public function test_service_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => '',
                'price' => -10,
                'duration_minutes' => 0,
            ]);

        $response->assertSessionHasErrors(['name', 'price', 'duration_minutes']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos);

        $priceInputPos = strpos($html, 'id="price"');
        $this->assertNotFalse($priceInputPos);
        $this->assertTrue(strpos($html, 'price') !== false);

        $durInputPos = strpos($html, 'id="duration_minutes"');
        $durErrorPos = strpos($html, 'The duration minutes field must be at least 1.');
        $this->assertNotFalse($durInputPos);
        $this->assertNotFalse($durErrorPos);
        $this->assertTrue($durErrorPos < $durInputPos);
    }

    public function test_category_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('categories.create'))
            ->post(route('categories.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors(['name']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos);
    }

    public function test_location_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('locations.create'))
            ->post(route('locations.store'), [
                'name' => '',
                'email' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors(['name', 'email']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos);

        $emailInputPos = strpos($html, 'id="email"');
        $emailErrorPos = strpos($html, 'The email field must be a valid email address.');
        $this->assertNotFalse($emailInputPos);
        $this->assertNotFalse($emailErrorPos);
        $this->assertTrue($emailErrorPos < $emailInputPos);
    }

    public function test_package_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('packages.create'))
            ->post(route('packages.store'), [
                'name' => '',
                'price' => -5,
            ]);

        $response->assertSessionHasErrors(['name', 'price']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos);

        $priceInputPos = strpos($html, 'id="price"');
        $this->assertNotFalse($priceInputPos);
    }

    public function test_client_create_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('clients.create'))
            ->post(route('clients.store'), [
                'first_name' => '',
                'email' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors(['first_name', 'email']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $fnInputPos = strpos($html, 'name="first_name"');
        $fnErrorPos = strpos($html, 'The first name field is required.');
        $this->assertNotFalse($fnInputPos);
        $this->assertNotFalse($fnErrorPos);
        $this->assertTrue($fnErrorPos < $fnInputPos, 'First name error must appear before first name input');

        $emailInputPos = strpos($html, 'name="email"');
        $emailErrorPos = strpos($html, 'The email field must be a valid email address.');
        $this->assertNotFalse($emailInputPos);
        $this->assertNotFalse($emailErrorPos);
        $this->assertTrue($emailErrorPos < $emailInputPos, 'Email error must appear before email input');
    }

    public function test_calendar_quick_client_ajax_422_returns_field_errors(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('calendar.quickClient'), [
                'first_name' => '',
                'email' => 'invalid-email-format',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'email']);
    }

    public function test_calendar_appointment_ajax_422_returns_field_errors(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('calendar.store'), [
                'staff_id' => '',
                'service_id' => '',
                'client_id' => '',
                'start_time' => '',
                'end_time' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['staff_id', 'service_id', 'client_id', 'start_time', 'end_time']);
    }

    public function test_all_audited_forms_contain_novalidate_attribute(): void
    {
        // 1. Login form
        $loginRes = $this->get(route('login'));
        $loginRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="loginForm"[^>]*novalidate/i', $loginRes->getContent());

        // 2. Staff index modals
        $staffIndexRes = $this->actingAs($this->admin, 'staff')->get(route('staff.index'));
        $staffIndexRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="add-staff-form"[^>]*novalidate/i', $staffIndexRes->getContent());
        $this->assertMatchesRegularExpression('/<form[^>]+id="edit-staff-form"[^>]*novalidate/i', $staffIndexRes->getContent());

        // 3. Client index modals
        $clientIndexRes = $this->actingAs($this->admin, 'staff')->get(route('clients.index'));
        $clientIndexRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="addClientForm"[^>]*novalidate/i', $clientIndexRes->getContent());
        $this->assertMatchesRegularExpression('/<form[^>]+id="editClientForm"[^>]*novalidate/i', $clientIndexRes->getContent());

        // 4. Services index modals
        $servicesRes = $this->actingAs($this->admin, 'staff')->get(route('services.index'));
        $servicesRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="add-service-form"[^>]*novalidate/i', $servicesRes->getContent());

        // 5. Calendar appointment & quick client
        $calendarRes = $this->actingAs($this->admin, 'staff')->get(route('calendar.index'));
        $calendarRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="appointment-form"[^>]*novalidate/i', $calendarRes->getContent());
        $this->assertMatchesRegularExpression('/<form[^>]+id="new-client-form"[^>]*novalidate/i', $calendarRes->getContent());

        // 6. Online booking form
        $bookingRes = $this->get(route('online-booking.index'));
        $bookingRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="bookingForm"[^>]*novalidate/i', $bookingRes->getContent());

        // 7. Payment records form
        $paymentRes = $this->actingAs($this->admin, 'staff')->get(route('payment-records.index'));
        $paymentRes->assertStatus(200);
        $this->assertMatchesRegularExpression('/<form[^>]+id="payment-record-form"[^>]*novalidate/i', $paymentRes->getContent());
    }

    public function test_payment_record_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('payment-records.index'))
            ->post(route('payment-records.store'), [
                'invoice_id' => '',
                'amount' => '',
                'payment_method' => '',
                'payment_date' => '',
            ]);

        $response->assertSessionHasErrors(['invoice_id', 'amount', 'payment_method', 'payment_date']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        // 1. Invoice error
        $invoiceInputPos = strpos($html, 'id="payment-invoice"');
        $invoiceErrorPos = strpos($html, 'Invoice is required.');
        $this->assertNotFalse($invoiceInputPos);
        $this->assertNotFalse($invoiceErrorPos);
        $this->assertTrue($invoiceErrorPos < $invoiceInputPos, 'Invoice error must appear before invoice select');

        // 2. Payment Method error
        $methodInputPos = strpos($html, 'id="pmt-method-select"');
        $methodErrorPos = strpos($html, 'Payment method is required.');
        $this->assertNotFalse($methodInputPos);
        $this->assertNotFalse($methodErrorPos);
        $this->assertTrue($methodErrorPos < $methodInputPos, 'Payment method error must appear before payment method select');

        // 3. Amount error
        $amountInputPos = strpos($html, 'id="payment-amount"');
        $amountErrorPos = strpos($html, 'Amount is required.');
        $this->assertNotFalse($amountInputPos);
        $this->assertNotFalse($amountErrorPos);
        $this->assertTrue($amountErrorPos < $amountInputPos, 'Amount error must appear before amount input');

        // 4. Payment Date error
        $dateInputPos = strpos($html, 'id="payment-date"');
        $dateErrorPos = strpos($html, 'Date is required.');
        $this->assertNotFalse($dateInputPos);
        $this->assertNotFalse($dateErrorPos);
        $this->assertTrue($dateErrorPos < $dateInputPos, 'Date error must appear before date input');
    }

    public function test_payment_record_zero_amount_validation_renders_directly_above_field(): void
    {
        $client = \App\Models\Client::create([
            'first_name' => 'Zero',
            'last_name' => 'AmountClient',
            'email' => 'zero@example.com',
            'phone' => '4165551234',
        ]);
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'client_id' => $client->id,
            'staff_id' => $this->admin->id,
            'total_amount' => 100.00,
            'status' => 'outstanding',
            'issued_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('payment-records.index'))
            ->post(route('payment-records.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 0,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['amount']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $amountInputPos = strpos($html, 'id="payment-amount"');
        $amountErrorPos = strpos($html, 'Paid amount must be greater than 0.');
        $this->assertNotFalse($amountInputPos);
        $this->assertNotFalse($amountErrorPos);
        $this->assertTrue($amountErrorPos < $amountInputPos, 'Zero amount error must appear before amount input');
    }

    public function test_payment_record_ajax_422_validation_errors(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('payment-records.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_id', 'amount', 'payment_method', 'payment_date']);
    }

    public function test_service_validation_errors_and_successful_save(): void
    {
        $category = \App\Models\ServiceCategory::create(['name' => 'Therapy']);

        // 1. Missing name, category, and price -> validation error
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('services.create'))
            ->post(route('services.store'), [
                'name' => '',
                'service_category_id' => '',
                'price' => '',
                'duration_minutes' => 60,
            ]);

        $response->assertSessionHasErrors(['name', 'service_category_id', 'price']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $nameInputPos = strpos($html, 'id="name"');
        $nameErrorPos = strpos($html, 'The name field is required.');
        $this->assertNotFalse($nameInputPos);
        $this->assertNotFalse($nameErrorPos);
        $this->assertTrue($nameErrorPos < $nameInputPos, 'Service name error must appear before name input');

        $catInputPos = strpos($html, 'id="service_category_id"');
        $catErrorPos = strpos($html, 'The category field is required.');
        $this->assertNotFalse($catInputPos);
        $this->assertNotFalse($catErrorPos);
        $this->assertTrue($catErrorPos < $catInputPos, 'Category error must appear before category select');

        // 2. Valid service save succeeds
        $validResponse = $this->actingAs($this->admin, 'staff')
            ->post(route('services.store'), [
                'name' => 'Full Body Massage',
                'service_category_id' => $category->id,
                'price' => 85.00,
                'duration_minutes' => 60,
            ]);

        $validResponse->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', [
            'name' => 'Full Body Massage',
            'service_category_id' => $category->id,
            'price' => 85.00,
        ]);
    }

    public function test_schedule_one_time_validation_errors_render_directly_above_fields(): void
    {
        // 1. Empty required fields for One Time recurrence
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('schedule.create'))
            ->post(route('schedule.store'), [
                'staff_id' => '',
                'location_id' => '',
                'recurrence_type' => 'one_time',
                'working_date' => '',
                'start_time' => '',
                'end_time' => '',
            ]);

        $response->assertSessionHasErrors(['staff_id', 'location_id', 'working_date', 'start_time', 'end_time']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        // Staff error before staff select
        $staffInputPos = strpos($html, 'id="staff_id"');
        $staffErrorPos = strpos($html, 'Staff is required.');
        $this->assertNotFalse($staffInputPos);
        $this->assertNotFalse($staffErrorPos);
        $this->assertTrue($staffErrorPos < $staffInputPos, 'Staff error must appear before staff select');

        // Location error before location select
        $locInputPos = strpos($html, 'id="location_id"');
        $locErrorPos = strpos($html, 'Location is required.');
        $this->assertNotFalse($locInputPos);
        $this->assertNotFalse($locErrorPos);
        $this->assertTrue($locErrorPos < $locInputPos, 'Location error must appear before location select');

        // Date error before date input
        $dateInputPos = strpos($html, 'id="working_date"');
        $dateErrorPos = strpos($html, 'Date is required.');
        $this->assertNotFalse($dateInputPos);
        $this->assertNotFalse($dateErrorPos);
        $this->assertTrue($dateErrorPos < $dateInputPos, 'Date error must appear before date input');

        // Start time error before start time input
        $startInputPos = strpos($html, 'id="start_time"');
        $startErrorPos = strpos($html, 'Start time is required.');
        $this->assertNotFalse($startInputPos);
        $this->assertNotFalse($startErrorPos);
        $this->assertTrue($startErrorPos < $startInputPos, 'Start time error must appear before start time input');

        // End time error before end time input
        $endInputPos = strpos($html, 'id="end_time"');
        $endErrorPos = strpos($html, 'End time is required.');
        $this->assertNotFalse($endInputPos);
        $this->assertNotFalse($endErrorPos);
        $this->assertTrue($endErrorPos < $endInputPos, 'End time error must appear before end time input');

        // Ensure weekly errors do NOT appear for One Time mode
        $this->assertStringNotContainsString('Start date is required.', $html);
        $this->assertStringNotContainsString('End date is required.', $html);
        $this->assertStringNotContainsString('Please select at least one day for weekly recurrence.', $html);

        // 2. Valid schedule -> successful save
        $tomorrow = now()->addDays(2)->format('Y-m-d');
        $validResponse = $this->actingAs($this->admin, 'staff')
            ->post(route('schedule.store'), [
                'staff_id' => $this->admin->id,
                'location_id' => $this->location->id,
                'recurrence_type' => 'one_time',
                'working_date' => $tomorrow,
                'start_time' => '09:00',
                'end_time' => '17:00',
            ]);

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('staff_schedules', [
            'staff_id' => $this->admin->id,
            'working_date' => $tomorrow . ' 00:00:00',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    public function test_schedule_weekly_validation_errors_render_directly_above_fields(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('schedule.create'))
            ->post(route('schedule.store'), [
                'staff_id' => '',
                'location_id' => '',
                'recurrence_type' => 'weekly',
                'start_date' => '',
                'end_date' => '',
                'start_time' => '',
                'end_time' => '',
                'weekly_days' => [],
            ]);

        $response->assertSessionHasErrors([
            'staff_id',
            'location_id',
            'start_date',
            'end_date',
            'start_time',
            'end_time',
            'weekly_days',
        ]);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        // Staff error
        $staffInputPos = strpos($html, 'id="staff_id"');
        $staffErrorPos = strpos($html, 'Staff is required.');
        $this->assertNotFalse($staffInputPos);
        $this->assertNotFalse($staffErrorPos);
        $this->assertTrue($staffErrorPos < $staffInputPos);

        // Location error
        $locInputPos = strpos($html, 'id="location_id"');
        $locErrorPos = strpos($html, 'Location is required.');
        $this->assertNotFalse($locInputPos);
        $this->assertNotFalse($locErrorPos);
        $this->assertTrue($locErrorPos < $locInputPos);

        // Start Date error
        $startDateInputPos = strpos($html, 'id="start_date"');
        $startDateErrorPos = strpos($html, 'Start date is required.');
        $this->assertNotFalse($startDateInputPos);
        $this->assertNotFalse($startDateErrorPos);
        $this->assertTrue($startDateErrorPos < $startDateInputPos);

        // End Date error
        $endDateInputPos = strpos($html, 'id="end_date"');
        $endDateErrorPos = strpos($html, 'End date is required.');
        $this->assertNotFalse($endDateInputPos);
        $this->assertNotFalse($endDateErrorPos);
        $this->assertTrue($endDateErrorPos < $endDateInputPos);

        // Start Time error
        $startTimeInputPos = strpos($html, 'id="start_time"');
        $startTimeErrorPos = strpos($html, 'Start time is required.');
        $this->assertNotFalse($startTimeInputPos);
        $this->assertNotFalse($startTimeErrorPos);
        $this->assertTrue($startTimeErrorPos < $startTimeInputPos);

        // End Time error
        $endTimeInputPos = strpos($html, 'id="end_time"');
        $endTimeErrorPos = strpos($html, 'End time is required.');
        $this->assertNotFalse($endTimeInputPos);
        $this->assertNotFalse($endTimeErrorPos);
        $this->assertTrue($endTimeErrorPos < $endTimeInputPos);

        // Weekly days error before checkbox group
        $weeklyGroupPos = strpos($html, 'class="weekday-checkbox-group"');
        $weeklyErrorPos = strpos($html, 'Please select at least one day for weekly recurrence.');
        $this->assertNotFalse($weeklyGroupPos);
        $this->assertNotFalse($weeklyErrorPos);
        $this->assertTrue($weeklyErrorPos < $weeklyGroupPos, 'Weekly days error must appear before weekdays group');

        // Ensure One-Time only error does NOT appear in Weekly mode
        $this->assertStringNotContainsString('Date is required.', $html);

        // Valid weekly schedule
        $startDate = now()->addDay()->format('Y-m-d');
        $endDate = now()->addWeeks(2)->format('Y-m-d');
        $validResponse = $this->actingAs($this->admin, 'staff')
            ->post(route('schedule.store'), [
                'staff_id' => $this->admin->id,
                'location_id' => $this->location->id,
                'recurrence_type' => 'weekly',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => '10:00',
                'end_time' => '16:00',
                'weekly_days' => [1, 3], // Monday and Wednesday
            ]);

        $validResponse->assertRedirect();
        $this->assertTrue(
            \App\Models\StaffSchedule::where('staff_id', $this->admin->id)
                ->where('start_time', '10:00')
                ->where('end_time', '16:00')
                ->count() > 0
        );
    }

    public function test_schedule_ajax_422_validation_errors(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->postJson(route('schedule.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['staff_id', 'location_id', 'recurrence_type', 'start_time', 'end_time']);
    }

    public function test_invoice_appointment_required_validation_and_successful_create(): void
    {
        $category = \App\Models\ServiceCategory::create(['name' => 'General Medical']);
        $service = \App\Models\Service::create([
            'service_category_id' => $category->id,
            'name' => 'General Checkup',
            'price' => 120.00,
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $client = \App\Models\Client::create([
            'first_name' => 'Robert',
            'last_name' => 'Smith',
            'email' => 'robert.smith@example.com',
            'phone' => '4165559876',
        ]);
        $appointment = \App\Models\Appointment::create([
            'client_id' => $client->id,
            'staff_id' => $this->admin->id,
            'service_id' => $service->id,
            'location_id' => $this->location->id,
            'start_time' => now()->addDay()->setHour(14)->setMinute(0),
            'end_time' => now()->addDay()->setHour(14)->setMinute(30),
            'status' => 'confirmed',
        ]);

        // 1. Missing appointment -> validation error
        $response = $this->actingAs($this->admin, 'staff')
            ->from(route('invoices.create'))
            ->post(route('invoices.store'), [
                'appointment_id' => '',
                'client_id' => $client->id,
                'staff_id' => $this->admin->id,
                'total_amount' => 120.00,
                'issued_date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['appointment_id']);
        $followResponse = $this->followRedirects($response);
        $html = $followResponse->getContent();

        $apptInputPos = strpos($html, 'id="appointment_id"');
        $apptErrorPos = strpos($html, 'The appointment field is required.');
        $this->assertNotFalse($apptInputPos);
        $this->assertNotFalse($apptErrorPos);
        $this->assertTrue($apptErrorPos < $apptInputPos, 'Appointment error must appear before appointment select');

        // 2. Invalid appointment_id -> validation error
        $invalidApptResponse = $this->actingAs($this->admin, 'staff')
            ->post(route('invoices.store'), [
                'appointment_id' => 999999,
                'client_id' => $client->id,
                'staff_id' => $this->admin->id,
                'total_amount' => 120.00,
                'issued_date' => now()->toDateString(),
            ]);

        $invalidApptResponse->assertSessionHasErrors(['appointment_id']);

        // 3. Valid appointment -> invoice is successfully created
        $validResponse = $this->actingAs($this->admin, 'staff')
            ->post(route('invoices.store'), [
                'appointment_id' => $appointment->id,
                'client_id' => $client->id,
                'staff_id' => $this->admin->id,
                'total_amount' => 120.00,
                'status' => 'outstanding',
                'issued_date' => now()->toDateString(),
            ]);

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'appointment_id' => $appointment->id,
            'client_id' => $client->id,
            'staff_id' => $this->admin->id,
            'total_amount' => 120.00,
        ]);
    }
}

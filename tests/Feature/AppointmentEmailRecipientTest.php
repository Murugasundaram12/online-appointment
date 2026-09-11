<?php

namespace Tests\Feature;

use App\Console\Commands\SendAppointmentReminders;
use App\Mail\AppointmentBookedMail;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentCompletedMail;
use App\Mail\AppointmentConfirmedMail;
use App\Mail\AppointmentNoShowMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\AppointmentUpdatedMail;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Location;
use App\Models\Service;
use App\Models\Staff;
use App\Services\AppointmentEmailService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentEmailRecipientTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;
    private Staff $staff;
    private Client $client;
    private Service $service;
    private AppointmentEmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->location = Location::create([
            'name' => 'Downtown Clinic',
            'address' => '123 Health Ave, Suite 100',
            'phone' => '555-0100',
            'email' => 'clinic@example.com',
            'timezone' => 'UTC',
            'is_active' => true,
        ]);

        $this->staff = Staff::create([
            'location_id' => $this->location->id,
            'name' => 'Dr. John Smith',
            'email' => 'john.smith@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
            'access_level' => 'staff',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonderland',
            'name' => 'Alice Wonderland',
            'email' => 'alice@example.com',
            'phone' => '555-0200',
        ]);

        $this->service = Service::create([
            'name' => 'Dental Checkup',
            'price' => 75.00,
            'duration_minutes' => 45,
            'buffer_minutes' => 0,
            'type' => 'in_person',
            'is_active' => true,
        ]);

        $this->emailService = app(AppointmentEmailService::class);
    }

    private function createTestAppointment(array $overrides = []): Appointment
    {
        $start = Carbon::now()->addDay()->setTime(10, 0);
        $end = $start->copy()->addMinutes(45);

        return Appointment::create(array_merge([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ], $overrides));
    }

    public function test_booked_appointment_sends_distinct_staff_and_client_emails(): void
    {
        $appointment = $this->createTestAppointment();
        $result = $this->emailService->sendBooked($appointment);

        $this->assertTrue($result['attempted']);
        $this->assertTrue($result['sent']);
        $this->assertNotNull($appointment->fresh()->confirmation_sent_at);

        // Client email check
        Mail::assertSent(AppointmentBookedMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertEquals('client', $mail->recipientType);
                $this->assertStringContainsString('Appointment Confirmation', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('mrclinicpro', $rendered);
                $this->assertStringNotContainsString('alt="Laravel"', $rendered);
                $this->assertStringContainsString('Hello Alice Wonderland,', $rendered);
                $this->assertStringContainsString('Your appointment has been booked successfully.', $rendered);
                $this->assertStringContainsString('Dr. John Smith', $rendered);
                $this->assertStringContainsString('$75.00', $rendered);
                return true;
            }
            return false;
        });

        // Staff email check
        Mail::assertSent(AppointmentBookedMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('staff', $mail->recipientType);
                $this->assertEquals('New Appointment Assigned – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('mrclinicpro', $rendered);
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                $this->assertStringContainsString('A new appointment has been assigned to you.', $rendered);
                $this->assertStringContainsString('Alice Wonderland', $rendered);
                $this->assertStringNotContainsString('Thank you for visiting us', $rendered);
                $this->assertStringNotContainsString('Your appointment has been booked successfully.', $rendered);
                return true;
            }
            return false;
        });
    }

    public function test_completed_appointment_sends_distinct_staff_and_client_emails(): void
    {
        $prev = $this->createTestAppointment(['status' => 'booked']);
        $appointment = clone $prev;
        $appointment->status = 'completed';
        $appointment->save();

        $result = $this->emailService->sendCompletedIfTransitioned($appointment, $prev);
        $this->assertTrue($result['sent']);

        // Client thank you email
        Mail::assertSent(AppointmentCompletedMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertStringContainsString('Thank You for Your Visit', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Thank you for visiting us', $rendered);
                $this->assertStringContainsString('We appreciate your feedback', $rendered);
                return true;
            }
            return false;
        });

        // Staff completed appointment email
        Mail::assertSent(AppointmentCompletedMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('Appointment Completed – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                $this->assertStringContainsString('The following appointment has been marked as completed.', $rendered);
                $this->assertStringNotContainsString('Thank you for visiting us', $rendered);
                $this->assertStringNotContainsString('We appreciate your feedback', $rendered);
                return true;
            }
            return false;
        });
    }

    public function test_cancelled_appointment_sends_reason_to_both_with_appropriate_wording(): void
    {
        $prev = $this->createTestAppointment(['status' => 'booked']);
        $appointment = clone $prev;
        $appointment->status = 'cancelled';
        $appointment->cancellation_reason = 'Doctor called away for emergency';
        $appointment->save();

        $result = $this->emailService->sendCancelledIfTransitioned($appointment, $prev);
        $this->assertTrue($result['sent']);

        // Client cancelled email
        Mail::assertSent(AppointmentCancelledMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $rendered = $mail->render();
                $this->assertStringContainsString('Your Appointment Has Been Cancelled', $rendered);
                $this->assertStringContainsString('Doctor called away for emergency', $rendered);
                return true;
            }
            return false;
        });

        // Staff cancelled email
        Mail::assertSent(AppointmentCancelledMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('Appointment Cancelled – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                $this->assertStringContainsString('The following appointment has been cancelled.', $rendered);
                $this->assertStringContainsString('Doctor called away for emergency', $rendered);
                return true;
            }
            return false;
        });
    }

    public function test_no_show_appointment_sends_distinct_staff_and_client_emails(): void
    {
        $prev = $this->createTestAppointment(['status' => 'booked']);
        $appointment = clone $prev;
        $appointment->status = 'no_show';
        $appointment->save();

        $result = $this->emailService->sendNoShowIfTransitioned($appointment, $prev);
        $this->assertTrue($result['sent']);

        // Client polite no show email
        Mail::assertSent(AppointmentNoShowMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertStringContainsString('Appointment Status', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Your appointment has been marked as No Show.', $rendered);
                $this->assertStringContainsString('please contact the clinic', $rendered);
                return true;
            }
            return false;
        });

        // Staff client no-show email
        Mail::assertSent(AppointmentNoShowMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('Client No-Show – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                $this->assertStringContainsString('The following appointment has been marked as No Show.', $rendered);
                return true;
            }
            return false;
        });
    }

    public function test_reminder_command_sends_distinct_emails_and_avoids_duplicates(): void
    {
        $appointment = $this->createTestAppointment([
            'start_time' => Carbon::now()->addHours(24),
            'end_time' => Carbon::now()->addHours(24)->addMinutes(45),
            'status' => 'booked',
            'reminder_sent_at' => null,
        ]);

        $this->artisan('appointments:send-reminders')
            ->assertExitCode(0);

        // Verify sent to both
        Mail::assertSent(AppointmentReminderMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertEquals('client', $mail->recipientType);
                $this->assertStringContainsString('Appointment Reminder –', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Hello Alice Wonderland,', $rendered);
                return true;
            }
            return false;
        });

        Mail::assertSent(AppointmentReminderMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('staff', $mail->recipientType);
                $this->assertEquals('Appointment Reminder – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                $this->assertStringContainsString('Upcoming Appointment Reminder', $rendered);
                return true;
            }
            return false;
        });

        $this->assertNotNull($appointment->fresh()->reminder_sent_at);

        // Running a second time does not re-send
        Mail::fake();
        $this->artisan('appointments:send-reminders')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_branding_displays_mrclinicpro_and_handles_missing_fields_safely(): void
    {
        $appointment = $this->createTestAppointment([
            'cancellation_reason' => null,
        ]);

        $mail = new AppointmentCancelledMail(
            $appointment,
            ['name' => 'Laravel', 'email' => 'test@example.com'],
            null,
            'APT-REF1234',
            'staff'
        );

        $mail->build();
        $rendered = $mail->render();

        $this->assertStringContainsString('mrclinicpro', $rendered);
        $this->assertStringNotContainsString('Laravel', $rendered);
        $this->assertStringContainsString('No reason provided.', $rendered);
    }

    public function test_confirmed_and_updated_appointments_send_distinct_emails(): void
    {
        $prev = $this->createTestAppointment(['status' => 'booked']);
        $appointment = clone $prev;
        $appointment->status = 'confirmed';
        $appointment->save();

        $result = $this->emailService->sendForStatusTransition($appointment, $prev);
        $this->assertTrue($result['sent']);

        Mail::assertSent(AppointmentConfirmedMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('staff', $mail->recipientType);
                $this->assertEquals('Appointment Confirmed – Alice Wonderland', $mail->subject);
                return true;
            }
            return false;
        });

        Mail::assertSent(AppointmentConfirmedMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertEquals('client', $mail->recipientType);
                $this->assertStringContainsString('Appointment Confirmed –', $mail->subject);
                return true;
            }
            return false;
        });

        // Test updated appointment
        Mail::fake();
        $updatedAppointment = clone $appointment;
        $updatedAppointment->start_time = Carbon::now()->addDays(3)->setTime(14, 0);
        $updatedAppointment->end_time = Carbon::now()->addDays(3)->setTime(15, 0);
        $updatedAppointment->save();

        $updateResult = $this->emailService->sendUpdatedIfRelevant($updatedAppointment, $appointment);
        $this->assertTrue($updateResult['sent']);

        Mail::assertSent(AppointmentUpdatedMail::class, function ($mail) {
            if ($mail->hasTo('john.smith@example.com')) {
                $mail->build();
                $this->assertEquals('staff', $mail->recipientType);
                $this->assertEquals('Appointment Updated – Alice Wonderland', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Appointment Updated', $rendered);
                $this->assertStringContainsString('Hello Dr. John Smith,', $rendered);
                return true;
            }
            return false;
        });

        Mail::assertSent(AppointmentUpdatedMail::class, function ($mail) {
            if ($mail->hasTo('alice@example.com')) {
                $mail->build();
                $this->assertEquals('client', $mail->recipientType);
                $this->assertStringContainsString('Appointment Updated –', $mail->subject);
                $rendered = $mail->render();
                $this->assertStringContainsString('Your Appointment Has Been Updated', $rendered);
                $this->assertStringContainsString('Hello Alice Wonderland,', $rendered);
                return true;
            }
            return false;
        });
    }

    public function test_appointment_time_matches_calendar_without_four_hour_shift(): void
    {
        // Set location timezone to America/New_York (EDT, UTC-4)
        $this->location->update(['timezone' => 'America/New_York']);

        $start = Carbon::parse('2026-09-11 11:15:00');
        $end = Carbon::parse('2026-09-11 12:45:00');

        $appointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'booked',
        ]);

        $business = $this->emailService->businessContext($appointment);
        $this->assertEquals('America/New_York', $business['timezone']);

        // 1. Staff booked email verification
        $staffMail = new AppointmentBookedMail($appointment, $business, null, 'APT-TEST001', 'staff');
        $staffMail->build();
        $staffRendered = $staffMail->render();
        $this->assertStringContainsString('11:15 AM – 12:45 PM', $staffRendered);
        $this->assertStringNotContainsString('7:15 AM', $staffRendered);
        $this->assertStringContainsString('Friday, September 11, 2026', $staffRendered);

        // 2. Client booked email verification
        $clientMail = new AppointmentBookedMail($appointment, $business, null, 'APT-TEST001', 'client');
        $clientMail->build();
        $clientRendered = $clientMail->render();
        $this->assertStringContainsString('11:15 AM – 12:45 PM', $clientRendered);
        $this->assertStringNotContainsString('7:15 AM', $clientRendered);
        $this->assertStringContainsString('Friday, September 11, 2026', $clientRendered);

        // 3. Reminder email verification
        $reminderMail = new AppointmentReminderMail($appointment, $business, 'APT-TEST001', 'client');
        $reminderMail->build();
        $reminderRendered = $reminderMail->render();
        $this->assertStringContainsString('11:15 AM – 12:45 PM', $reminderRendered);
        $this->assertStringNotContainsString('7:15 AM', $reminderRendered);

        // 4. Second appointment time: 2:30 PM – 3:30 PM
        $afternoonAppointment = Appointment::create([
            'client_id' => $this->client->id,
            'staff_id' => $this->staff->id,
            'service_id' => $this->service->id,
            'location_id' => $this->location->id,
            'start_time' => Carbon::parse('2026-09-11 14:30:00'),
            'end_time' => Carbon::parse('2026-09-11 15:30:00'),
            'status' => 'booked',
        ]);

        $afternoonMail = new AppointmentBookedMail($afternoonAppointment, $business, null, 'APT-TEST002', 'staff');
        $afternoonMail->build();
        $afternoonRendered = $afternoonMail->render();
        $this->assertStringContainsString('2:30 PM – 3:30 PM', $afternoonRendered);
        $this->assertStringNotContainsString('10:30 AM', $afternoonRendered);
    }
}

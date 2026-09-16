<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Form;
use App\Models\FormRecord;
use App\Models\Location;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormRecordShowTest extends TestCase
{
    use RefreshDatabase;

    private Staff $staff;
    private Client $client;
    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        $location = Location::create([
            'name' => 'Main Center',
            'timezone' => 'America/Toronto',
            'is_active' => true,
        ]);

        $this->staff = Staff::create([
            'location_id' => $location->id,
            'name' => 'Admin Staff',
            'email' => 'form_admin@example.com',
            'password' => Hash::make('Password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice@example.com',
            'phone' => '1234567890',
        ]);

        $this->form = Form::create([
            'name' => 'Intake Form',
            'fields' => [
                ['name' => 'Name', 'type' => 'text', 'required' => true],
                ['name' => 'Medical History', 'type' => 'text', 'required' => false],
                ['name' => 'Allergies', 'type' => 'text', 'required' => false],
            ],
            'is_active' => true,
        ]);
    }

    public function test_form_record_details_renders_successfully_with_double_encoded_json_data(): void
    {
        // Simulate record 2 which had raw double-encoded JSON string
        $record = new FormRecord();
        $record->form_id = $this->form->id;
        $record->client_id = $this->client->id;
        $record->setRawAttributes([
            'form_id' => $this->form->id,
            'client_id' => $this->client->id,
            'submitted_data' => '"{\"Name\":\"Alice Johnson\",\"Medical History\":\"None\",\"Allergies\":\"Peanuts\"}"',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $record->save();

        $response = $this->actingAs($this->staff, 'staff')
            ->get(route('form-records.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('Intake Form');
        $response->assertSee('Alice Johnson');
        $response->assertSee('Medical History');
        $response->assertSee('None');
        $response->assertSee('Allergies');
        $response->assertSee('Peanuts');
    }

    public function test_form_record_details_renders_successfully_with_array_data(): void
    {
        $record = FormRecord::create([
            'form_id' => $this->form->id,
            'client_id' => $this->client->id,
            'submitted_data' => [
                'full_name' => 'Bob Smith',
                'notes' => 'Regular checkup',
            ],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staff, 'staff')
            ->get(route('form-records.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('Bob Smith');
        $response->assertSee('Regular checkup');
    }

    public function test_form_record_details_handles_empty_submitted_data(): void
    {
        $record = FormRecord::create([
            'form_id' => $this->form->id,
            'client_id' => $this->client->id,
            'submitted_data' => [],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->staff, 'staff')
            ->get(route('form-records.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('No submitted data recorded.');
    }

    public function test_form_record_returns_404_when_not_found(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->get(route('form-records.show', 99999));

        $response->assertStatus(404);
    }

    public function test_form_record_create_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->staff, 'staff')
            ->get(route('form-records.create'));

        $response->assertStatus(200);
        $response->assertSee('Create form record');
        $response->assertSee('Intake Form');
    }

    public function test_form_record_store_succeeds_with_form_having_double_encoded_questions(): void
    {
        $form = new Form();
        $form->setRawAttributes([
            'name' => 'Questionnaire',
            'description' => 'Test',
            'fields' => '"{\"questions\":[\"Name\",\"Medical History\",\"Allergies\"]}"',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $form->save();

        $response = $this->actingAs($this->staff, 'staff')
            ->post(route('form-records.store'), [
                'form_id' => $form->id,
                'client_id' => $this->client->id,
                'submitted_data' => [
                    'Name' => 'John Doe',
                    'Medical History' => 'None',
                    'Allergies' => 'None',
                ],
            ]);

        $response->assertRedirect(route('form-records.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('form_records', [
            'form_id' => $form->id,
            'client_id' => $this->client->id,
        ]);
    }
}

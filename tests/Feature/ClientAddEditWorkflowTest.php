<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAddEditWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Staff $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Staff::create([
            'name' => 'Admin Staff',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'access_level' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_client_index_renders_edit_button_and_isolated_modals(): void
    {
        $clientA = Client::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice@example.com',
            'phone' => '4161112233',
            'dob' => '1995-04-12',
            'client_since' => '2023-01-10',
            'is_vip' => true,
        ]);

        $clientB = Client::create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'email' => 'bob@example.com',
            'phone' => '4164445566',
            'dob' => '1988-10-25',
            'client_since' => '2024-06-01',
            'is_vip' => false,
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->get(route('clients.index'));

        $response->assertStatus(200);
        $html = $response->getContent();

        // Verify edit buttons have all data attributes
        $this->assertStringContainsString('data-client-id="' . $clientA->id . '"', $html);
        $this->assertStringContainsString('data-first-name="Alice"', $html);
        $this->assertStringContainsString('data-last-name="Smith"', $html);
        $this->assertStringContainsString('data-update-url="' . route('clients.update', $clientA->id) . '"', $html);

        $this->assertStringContainsString('data-client-id="' . $clientB->id . '"', $html);
        $this->assertStringContainsString('data-first-name="Bob"', $html);
        $this->assertStringContainsString('data-last-name="Jones"', $html);
        $this->assertStringContainsString('data-update-url="' . route('clients.update', $clientB->id) . '"', $html);

        // Edit client modal form should have empty initial inputs (not bound to the last client)
        $this->assertStringContainsString('id="editClientModal"', $html);
        $this->assertStringContainsString('id="editClientForm"', $html);
        $this->assertStringContainsString('id="edit-client-first-name"', $html);
        $this->assertStringContainsString('id="add-client-first-name"', $html);
    }

    public function test_client_can_be_updated_successfully(): void
    {
        $client = Client::create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'email' => 'original@example.com',
            'phone' => '4161234567',
            'is_vip' => false,
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('clients.update', $client->id), [
                'first_name' => 'Updated',
                'last_name' => 'User',
                'email' => 'updated@example.com',
                'phone' => '4169998888',
                'gender' => 'female',
                'dob' => '1992-08-15',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'is_vip' => '1',
            ]);

        $response->assertRedirect(route('clients.show', $client->id));
        $client->refresh();

        $this->assertSame('Updated User', $client->name);
        $this->assertSame('Updated', $client->first_name);
        $this->assertSame('User', $client->last_name);
        $this->assertSame('updated@example.com', $client->email);
        $this->assertSame('female', $client->gender);
        $this->assertSame('Toronto', $client->city);
        $this->assertTrue($client->is_vip);
    }

    public function test_client_fallback_accessors_for_legacy_records(): void
    {
        $legacy = Client::create([
            'name' => 'Legacy Customer',
            'email' => 'legacy@example.com',
        ]);

        // Manually unset first_name / last_name to test fallback accessor
        $legacy->first_name = null;
        $legacy->last_name = null;
        $legacy->saveQuietly();

        $legacy->refresh();
        $this->assertSame('Legacy', $legacy->first_name);
        $this->assertSame('Customer', $legacy->last_name);
    }

    public function test_client_can_be_created_with_only_first_and_last_name(): void
    {
        $response = $this->actingAs($this->admin, 'staff')
            ->post(route('clients.store'), [
                'first_name' => 'Minimal',
                'last_name' => 'Client',
            ]);

        $response->assertSessionHasNoErrors();
        $client = Client::where('first_name', 'Minimal')->where('last_name', 'Client')->first();
        $this->assertNotNull($client);
        $this->assertSame('Minimal Client', $client->name);
        $this->assertNull($client->email);
        $this->assertNull($client->phone);
        $this->assertNull($client->dob);
        $this->assertNull($client->city);
    }

    public function test_client_can_be_updated_with_only_first_and_last_name(): void
    {
        $client = Client::create([
            'first_name' => 'Initial',
            'last_name' => 'Person',
            'email' => 'person@example.com',
            'phone' => '4161112222',
        ]);

        $response = $this->actingAs($this->admin, 'staff')
            ->put(route('clients.update', $client->id), [
                'first_name' => 'Initial',
                'last_name' => 'PersonRenamed',
                'email' => '',
                'phone' => '',
            ]);

        $response->assertSessionHasNoErrors();
        $client->refresh();
        $this->assertSame('Initial PersonRenamed', $client->name);
        $this->assertNull($client->email);
        $this->assertNull($client->phone);
    }
}

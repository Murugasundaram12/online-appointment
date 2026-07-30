<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Client::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'phone' => '555-2001',
            'city' => 'New York',
            'client_since' => '2023-01-15',
            'notes' => 'Prefers morning appointments.',
        ]);

        Client::create([
            'name' => 'Bob Williams',
            'email' => 'bob@example.com',
            'phone' => '555-2002',
            'city' => 'Brooklyn',
            'client_since' => '2023-03-20',
            'notes' => 'Allergic to latex.',
        ]);

        Client::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'phone' => '555-2003',
            'city' => 'Queens',
            'client_since' => '2023-05-10',
            'notes' => 'New patient.',
        ]);
    }
}

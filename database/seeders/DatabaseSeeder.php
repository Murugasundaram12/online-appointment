<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LocationSeeder::class,
            StaffSeeder::class,
            ClientSeeder::class,
            ServiceCategorySeeder::class,
            ServiceSeeder::class,
            PackageSeeder::class,
            StaffScheduleSeeder::class,
            AppointmentSeeder::class,
            InvoiceSeeder::class,
            PaymentRecordSeeder::class,
            FormSeeder::class,
            FormRecordSeeder::class,
            PayrollSeeder::class,
        ]);
    }
}

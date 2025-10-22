<?php

namespace Database\Seeders;

use App\Models\WarehouseLocation;
use Illuminate\Database\Seeder;
// use App\Models\Models\WarehouseLocation;

class WarehouseLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Main Warehouse',
                'description' => 'Primary warehouse for all incoming and outgoing items.',
                'address' => '123 Industrial Ave, Manila',
            ],
            [
                'name' => 'Secondary Storage',
                'description' => 'Backup warehouse for overflow inventory.',
                'address' => '45 Logistic Rd, Quezon City',
            ],
            [
                'name' => 'Project Site Storage',
                'description' => 'Temporary location for project-based materials.',
                'address' => 'Site B, Pasig City',
            ],
        ];

        foreach ($locations as $loc) {
            WarehouseLocation::create($loc);
        }

        $this->command->info('✅ Warehouse locations seeded successfully.');
    }
}

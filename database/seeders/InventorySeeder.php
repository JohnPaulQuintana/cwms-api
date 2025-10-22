<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\WarehouseLocation;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainWarehouse = WarehouseLocation::first();

        if (!$mainWarehouse) {
            $this->command->warn('⚠️ No warehouse locations found. Run WarehouseLocationSeeder first.');
            return;
        }

        $items = [
            [
                'name' => 'Cement Bags',
                'sku' => 'CEM-001',
                'description' => 'High-quality Portland cement for construction.',
                'quantity' => 250,
                'unit' => 'bags',
                'location_id' => $mainWarehouse->id,
            ],
            [
                'name' => 'Steel Rods 10mm',
                'sku' => 'STEEL-10',
                'description' => 'Reinforcement rods for concrete structures.',
                'quantity' => 100,
                'unit' => 'pieces',
                'location_id' => $mainWarehouse->id,
            ],
            [
                'name' => 'Paint (White)',
                'sku' => 'PAINT-WHT',
                'description' => 'Premium white paint for finishing works.',
                'quantity' => 75,
                'unit' => 'gallons',
                'location_id' => $mainWarehouse->id,
            ],
        ];

        foreach ($items as $item) {
            Inventory::create($item);
        }

        $this->command->info('✅ Inventory items seeded successfully.');
    }
}

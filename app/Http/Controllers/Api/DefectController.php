<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DefectItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefectController extends Controller
{
    //get defected items
    public function getDefectItems(Request $request)
    {
        $defectItems = DefectItem::with('inventory', 'shipment')->paginate(10);

        return response()->json([
            'success' => true,
            'm,essage' => 'Defect items retrieved successfully',
            'data' => $defectItems,
        ]);
    }

    //store defect item
    public function addDefectItem(Request $request)
    {
        // 1. Validate the request
        $validated = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'inventory' => 'required|array|min:1',
            'inventory.*.inventory_id' => 'required|integer|exists:inventories,id',
            'inventory.*.quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'status' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {

            // 3. Loop through each inventory item
            foreach ($validated['inventory'] as $item) {
                DefectItem::create([
                    'shipment_id'     => $validated['shipment_id'],
                    'inventory_id'    => $item['inventory_id'],
                    'quantity'        => $item['quantity'],
                    'reason'          => $validated['reason'],
                    'status'          => $validated['status'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Defect items stored successfully',
                'data' => $validated,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to store defect item requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

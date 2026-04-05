<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryRequest;
use App\Models\ReturnedInventory;
use Illuminate\Http\Request;

class InventoryReturnedController extends Controller
{
    // get returned items
    public function getReturnedItems(Request $request)
    {
        $query = ReturnedInventory::query();

        // ✅ Filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // ✅ Always latest on top
        $query->orderBy('created_at', 'desc');

        $results = $query->paginate(10);

        return response()->json([
            'success' => true,
            'm,essage' => 'Returned items retrieved successfully',
            'data' => $results,
        ]);
    }

    public function returnInventory(Request $request)
    {
        $request->validate([
            'inventory_request_id' => 'required|integer|exists:inventory_requests,id',
            'inventory_name' => 'required|string',
            'project_id' => 'required|integer',
            'warehouse_name' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string',
        ]);

        $data = $request->only([
            'inventory_request_id',
            'inventory_name',
            'project_id',
            'warehouse_name',
            'quantity',
            'unit',
        ]);

        $data['status'] = 'pending';

        $returnedItem = ReturnedInventory::create($data);

        return response()->json([
            'success' => true,
            'data' => $returnedItem,
        ]);
    }

    public function approveReturn($id)
    {
        $returnedItem = ReturnedInventory::findOrFail($id);
        $returnedItem->status = 'approved';
        $returnedItem->save();

        return response()->json([
            'success' => true,
            'data' => $returnedItem,
        ]);
    }

    public function mergeReturn($id)
    {
        $returnedItem = ReturnedInventory::findOrFail($id);

        if ($returnedItem->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved returns can be merged',
            ], 400);
        }

        // Update actual inventory quantity
        $inventoryRequest = InventoryRequest::findOrFail($returnedItem->inventory_request_id); // or map to inventory id

        // Get the actual inventory using inventory_id
        $inventory = Inventory::findOrFail($inventoryRequest->inventory_id);

        $inventory->quantity += $returnedItem->quantity;
        $inventory->save();

        // Mark return as merged
        $returnedItem->status = 'merged';
        $returnedItem->save();

        return response()->json([
            'success' => true,
            'data' => $returnedItem,
        ]);
    }
}

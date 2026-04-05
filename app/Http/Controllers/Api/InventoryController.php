<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\InventoryReorder;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Inventory::class);

        $search = $request->input('search');
        $warehouseId = $request->input('warehouse_id');

        // Load inventories with location and reorder history
        $query = Inventory::with(['location', 'reorders' => function ($q) {
            $q->orderBy('created_at', 'desc'); // most recent first
        }])
            ->when($warehouseId, function ($q) use ($warehouseId) {
                $q->where('location_id', $warehouseId);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $inventories = $query->paginate(20);

        \Log::info('Filter:', [
            'warehouse_id' => $warehouseId,
            'search' => $search,
            'count' => $query->count(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $inventories,
        ]);
    }

    public function index_staff(Request $request)
    {
        $user = auth()->user(); // get the authenticated user

        // 🔐 Authorization check (optional)
        $this->authorize('viewAny', Inventory::class);

        // Get query parameters
        $search = $request->input('search');

        // 🏭 Find the warehouse location where this user is assigned as staff
        $warehouseLocation = WarehouseLocation::where('staff_id', $user->id)->first();

        if (! $warehouseLocation) {
            return response()->json([
                'success' => false,
                'message' => 'No warehouse location assigned to this staff.',
                'data' => [],
            ], 404);
        }

        // 🔍 Build the query for inventories under that location
        $query = Inventory::with('location')
            ->where('location_id', $warehouseLocation->id)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        $inventories = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $inventories,
        ]);
    }

    public function show($id)
    {
        $inv = Inventory::with('location')->findOrFail($id);
        $this->authorize('view', $inv);

        return response()->json(['success' => true, 'data' => $inv]);
    }

    public function store(StoreInventoryRequest $request)
    {
        $this->authorize('create', Inventory::class);
        $inv = Inventory::create($request->validated());

        return response()->json(['success' => true, 'data' => $inv], 201);
    }

    public function update(UpdateInventoryRequest $request, $id)
    {
        $inv = Inventory::findOrFail($id);
        $this->authorize('update', $inv);
        $inv->update($request->validated());

        return response()->json(['success' => true, 'data' => $inv]);
    }

    public function destroy($id)
    {
        $inv = Inventory::findOrFail($id);
        $this->authorize('delete', $inv);
        $inv->delete();

        return response()->json(['success' => true, 'message' => 'Inventory deleted']);
    }

    public function reorder(Request $request, $id)
    {
        $inv = Inventory::findOrFail($id);
        $this->authorize('update', $inv); // Ensure the user has permission

        // Validate the requested quantity
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $quantity = $request->input('quantity');

        // 1️⃣ Update reorder_quantity on inventory
        $inv->reorder_quantity += $quantity;
        $inv->save();

        // 2️⃣ Create a reorder history record
        $reorder = InventoryReorder::create([
            'inventory_id' => $inv->id,
            'user_id' => auth()->id(),
            'quantity' => $quantity,
            'status' => 'pending', // default status
        ]);

        return response()->json([
            'success' => true,
            'message' => "Reordered {$quantity} units for '{$inv->name}'",
            'data' => [
                'inventory' => $inv,
                'reorder' => $reorder,
            ],
        ]);
    }

    public function mergeSingle($id)
    {
        $reorder = InventoryReorder::findOrFail($id);

        if ($reorder->status === 'merged') {
            return response()->json(['message' => 'Already merged'], 400);
        }

        $inventory = $reorder->inventory;

        $inventory->quantity += $reorder->quantity;
        $inventory->reorder_quantity -= $reorder->quantity;
        $inventory->save();

        $reorder->status = 'merged';
        // $reorder->merged_at = now();
        $reorder->save();

        return response()->json(['success' => true]);
    }

    public function mergeAll($inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        $pending = $inventory->reorders()
            ->where('status', 'pending')
            ->get();

        $total = $pending->sum('quantity');

        if ($total === 0) {
            return response()->json(['message' => 'No pending reorders'], 400);
        }

        $inventory->quantity += $total;
        $inventory->reorder_quantity -= $total;
        $inventory->save();

        foreach ($pending as $r) {
            $r->status = 'merged';
            // $r->merged_at = now();
            $r->save();
        }

        return response()->json(['success' => true]);
    }
}

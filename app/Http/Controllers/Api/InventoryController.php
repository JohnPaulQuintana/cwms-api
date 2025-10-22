<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;

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

        $query = Inventory::with('location')
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

        \Log::info('Filter:', ['warehouse_id' => $warehouseId, 'search' => $search, 'count' => $query->count()]);

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
        $warehouseLocation = \App\Models\WarehouseLocation::where('staff_id', $user->id)->first();

        if (!$warehouseLocation) {
            return response()->json([
                'success' => false,
                'message' => 'No warehouse location assigned to this staff.',
                'data' => [],
            ], 404);
        }

        // 🔍 Build the query for inventories under that location
        $query = \App\Models\Inventory::with('location')
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
}

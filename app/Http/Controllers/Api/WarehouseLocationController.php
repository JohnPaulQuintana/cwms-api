<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WarehouseLocation;
use App\Http\Requests\StoreLocationRequest;
use App\Models\InventoryRequest;
use Illuminate\Http\Request;

class WarehouseLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        $search = $request->query('search'); // optional search keyword
        $perPage = $request->query('per_page', 10); // default to 10 per page

        $query = WarehouseLocation::query();

        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Paginate and sort by latest
        $warehouses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $warehouses
        ]);
    }

    public function inventory_staff(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search'); // optional search keyword
        $perPage = $request->query('per_page', 10); // default to 10 per page

        $query = WarehouseLocation::query();

        // Apply search filter if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Paginate and sort by latest
        $warehouses = $query->orderBy('created_at', 'desc')->where('staff_id', $user->id)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $warehouses
        ]);
    }


    public function store(StoreLocationRequest $request)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'warehouse_staff'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $loc = WarehouseLocation::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $loc
        ], 201);
    }

    // Update warehouse
    public function update(StoreLocationRequest $request, $id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'warehouse_staff'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $loc = WarehouseLocation::find($id);

        if (!$loc) {
            return response()->json(['success' => false, 'message' => 'Warehouse not found'], 404);
        }

        $loc->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $loc
        ]);
    }

    // Delete warehouse
    public function destroy($id)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['admin', 'warehouse_staff'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $loc = WarehouseLocation::find($id);

        if (!$loc) {
            return response()->json(['success' => false, 'message' => 'Warehouse not found'], 404);
        }

        $loc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully'
        ]);
    }

    //get warehouse records
    public function getWarehouseRecords(Request $request, $id)
    {
        //get inventory requests with warehouse id
        $warehouses = InventoryRequest::with(['inventory.defectItems','requester','project','shipmentItem'])->where('warehouse_id', $id)->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse records retrieved successfully',
            'data' => $warehouses,
        ]);
    }
}

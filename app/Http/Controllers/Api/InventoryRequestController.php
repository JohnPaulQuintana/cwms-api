<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\InventoryRequest;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryRequestController extends Controller
{

    public function index(Request $request)
    {
        $this->authorizeRole(['project_manager', 'admin']); // adjust roles as needed

        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'page' => 'nullable|integer|min:1',
            'sort_field' => 'nullable|string',
            'sort_order' => 'nullable|in:asc,desc',
        ]);

        $query = InventoryRequest::with(['inventory', 'warehouse', 'project']);

        // Project filter
        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        // Sorting
        $sortField = $request->sort_field ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        // Ensure sorting field exists on table
        $allowedSort = ['id', 'created_at', 'requested_qty'];
        if (!in_array($sortField, $allowedSort)) {
            $sortField = 'created_at';
        }

        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = 10;
        $requests = $query->paginate($perPage);

        // Map for frontend
        $requests->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'inventory_name' => $item->inventory->name,
                'requested_qty' => $item->requested_qty,
                'warehouse_name' => $item->warehouse->name,
                'unit' => $item->inventory->unit,
                'created_at' => $item->created_at,
                'project_id' => $item->project_id,
                'project_name' => $item->project->name,
                'status' => $item->status,
                'reject_reason' => $item->rejection_reason,
            ];
        });

        return response()->json($requests, 200);
    }


    public function store(Request $request)
    {

        $this->authorizeRole(['project_manager']);

        $request->validate([
            // 'warehouse_id' => 'required|exists:warehouse_locations,id',
            'project_id' => 'required|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.requested_qty' => 'required|integer|min:1',
            'items.*.warehouse_id' => 'required|exists:warehouse_locations,id',
        ]);

        // $warehouseId = $request->warehouse_id;
        $projectId = $request->project_id;
        $items = $request->items;

        DB::beginTransaction();


        try {
            $responses = [];

            foreach ($items as $item) {
                $inventory = Inventory::find($item['inventory_id']);

                // // Check stock
                // if ($inventory->quantity < $item['requested_qty']) {
                //     DB::rollBack();
                //     return response()->json([
                //         'error' => "Not enough stock for {$inventory->name}"
                //     ], 400);
                // }

                // Create inventory request
                $inventoryRequest = InventoryRequest::create([
                    'inventory_id' => $inventory->id,
                    'warehouse_id' => $item['warehouse_id'],
                    'requested_qty' => $item['requested_qty'],
                    'requested_by' => $request->user()->id,
                    'project_id' => $projectId,
                ]);

                // Reduce inventory stock immediately
                // $inventory->decrement('quantity', $item['requested_qty']);

                $responses[] = $inventoryRequest;
            }

            DB::commit();

            return response()->json([
                'message' => 'Inventory request submitted successfully',
                'data' => $responses
            ], 201);
        } catch (\Exception $e) {
            //\Exception $e;
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $request = InventoryRequest::findOrFail($id);
        $this->authorizeRole(['project_manager']);

        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($request->inventory_id);

            // Return the requested quantity back to stock
            $inventory->increment('quantity', $request->requested_qty);

            $request->delete();

            DB::commit();

            return response()->json(['message' => 'Request deleted and stock restored'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $inventoryRequest = InventoryRequest::findOrFail($id);
        $this->authorizeRole(['project_manager']);

        $request->validate([
            'requested_qty' => 'sometimes|integer|min:1',
            'warehouse_id' => 'sometimes|exists:warehouse_locations,id',
        ]);

        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($inventoryRequest->inventory_id);

            // Step 1: restore old stock
            $inventory->increment('quantity', $inventoryRequest->requested_qty);

            // Step 2: if requested_qty is changed, deduct the new one
            if ($request->has('requested_qty')) {
                if ($inventory->quantity < $request->requested_qty) {
                    DB::rollBack();
                    return response()->json([
                        'error' => "Not enough stock for {$inventory->name}"
                    ], 400);
                }

                $inventory->decrement('quantity', $request->requested_qty);
            }

            // Step 3: update the request record
            $inventoryRequest->update($request->only(['requested_qty', 'warehouse_id']));

            DB::commit();

            return response()->json([
                'message' => 'Request updated successfully and stock adjusted',
                'data' => $inventoryRequest,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // get inventory request details
    public function getInventoryRequestDetails(Request $request)
    {
        $this->authorizeRole(['warehouse_staff']);

        $user = auth()->user();

        // get all warehouse location IDs owned by this user
        $warehouseIds = $user->warehouseLocations()->pluck('id');

        // query inventory requests related to those warehouses
        $inventoryRequests = InventoryRequest::with(['requester', 'inventory', 'project'])
            ->whereIn('warehouse_id', $warehouseIds)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10)); // default 10 per page

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'warehouse_locations' => $warehouseIds,
                'inventory_requests' => $inventoryRequests,
            ],
        ]);
    }

    // handle request approval/rejection by warehouse staff
    public function handleAction(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approved,rejected',
            'reason' => 'nullable|string|max:500', // ✅ new
        ]);

        $inventoryRequest = InventoryRequest::with('inventory')->findOrFail($id);

        if ($request->action === 'approved') {
            // ✅ Subtract the quantity if approved
            $inventory = $inventoryRequest->inventory;
            if ($inventory && $inventory->quantity >= $inventoryRequest->requested_qty) {
                $inventory->quantity -= $inventoryRequest->requested_qty;
                $inventory->save();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock for this approval.',
                ], 422);
            }

            // ✅ Find or create shipment for the same project
            $shipment = Shipment::firstOrCreate(
                [
                    'project_id' => $inventoryRequest->project_id,
                    'tracking_number' => strtoupper(Str::random(10)),
                    'status' => 'pending',
                ],
                [
                    'user_id' => auth()->id(),
                ]
            );

            // ✅ Add this request as a shipment item
            ShipmentItem::create([
                'shipment_id' => $shipment->id,
                'inventory_request_id' => $inventoryRequest->id,
                'quantity' => $inventoryRequest->requested_qty,
            ]);

            // $inventoryRequest->status = 'approved';
            // $inventoryRequest->shipment_ready = true;
            $inventoryRequest->rejection_reason = null;
        } elseif ($request->action === 'rejected') {
            // ✅ Add back quantity if previously deducted (optional)
            $inventory = $inventoryRequest->inventory;
            if ($inventory) {
                $inventory->quantity += $inventoryRequest->requested_qty;
                $inventory->save();
            }

            $inventoryRequest->rejection_reason = $request->reason;
        }

        $inventoryRequest->status = $request->action;
        $inventoryRequest->save();

        return response()->json([
            'success' => true,
            'message' => "Request {$request->action} successfully. " .
                ($request->action === 'approved' ? 'Added to shipment list.' : ''),
            'data' => $inventoryRequest->load('inventory'),
        ]);
    }






    //  Utility function for role-based restrictions
    private function authorizeRole(array $roles)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, $roles)) {
            abort(403, 'Access denied');
        }
    }
}

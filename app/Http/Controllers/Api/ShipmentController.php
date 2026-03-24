<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShipmentController extends Controller
{
    // Create a shipment
    public function store(Request $request)
    {
        $this->authorizeRole(['project_manager', 'admin']);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'requests' => 'required|array|min:1',
            'requests.*.id' => 'required|exists:inventory_requests,id',
            'requests.*.quantity' => 'required|integer|min:1',
        ]);

        $shipment = Shipment::create([
            'project_id' => $validated['project_id'],
            'user_id' => auth()->id(),
            'tracking_number' => strtoupper(Str::random(10)),
            'status' => 'pending',
        ]);

        foreach ($validated['requests'] as $req) {
            ShipmentItem::create([
                'shipment_id' => $shipment->id,
                'inventory_request_id' => $req['id'],
                'quantity' => $req['quantity'],
            ]);

            // Optional: mark the inventory request as shipped
            // InventoryRequest::where('id', $req['id'])->update(['status' => 'shipped']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shipment created successfully',
            'shipment' => $shipment->load('items.request.inventory', 'project', 'user'),
        ]);
    }

    // List all shipments
    public function index()
    {
        $this->authorizeRole(['project_manager', 'admin', 'warehouse_staff']);

        $shipments = Shipment::with(['project', 'user', 'items.inventoryRequest.inventory'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $shipments,
        ]);
    }

    // View a single shipment
    public function show($id)
    {
        $shipment = Shipment::with(['project', 'user', 'items.inventoryRequest.inventory'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $shipment,
        ]);
    }

    // ShipmentController.php
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:in_transit,delivered',
        ]);

        $shipment = Shipment::findOrFail($id);
        $shipment->status = $request->status;
        $shipment->save();

        return response()->json([
            'success' => true,
            'message' => "Shipment marked as {$request->status}.",
            'data' => $shipment,
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

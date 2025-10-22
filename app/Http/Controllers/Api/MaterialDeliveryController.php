<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaterialDelivery;

class MaterialDeliveryController extends Controller
{
    // List all deliveries
    public function index()
    {
        $deliveries = MaterialDelivery::with(['inventory', 'project', 'deliveredBy'])->latest()->get();
        return response()->json(['success' => true, 'data' => $deliveries]);
    }

    // View single delivery
    public function show($id)
    {
        $delivery = MaterialDelivery::with(['inventory', 'project', 'deliveredBy'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $delivery]);
    }

    // Create delivery
    public function store(Request $request)
    {
        $this->authorizeRole(['admin', 'warehouse_staff']);

        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'quantity' => 'required|integer|min:1',
            'delivery_type' => 'required|in:incoming,outgoing',
            'project_id' => 'nullable|exists:projects,id',
            'delivery_date' => 'nullable|date',
        ]);

        $validated['delivered_by'] = auth()->id();

        $delivery = MaterialDelivery::create($validated);
        return response()->json(['success' => true, 'data' => $delivery]);
    }

    // Update delivery
    public function update(Request $request, $id)
    {
        $this->authorizeRole(['admin', 'warehouse_staff']);

        $delivery = MaterialDelivery::findOrFail($id);
        $delivery->update($request->only(['quantity', 'delivery_type', 'delivery_date', 'project_id']));
        return response()->json(['success' => true, 'data' => $delivery]);
    }

    // Approve
    public function approve($id)
    {
        $this->authorizeRole(['admin', 'project_manager']);

        $delivery = MaterialDelivery::findOrFail($id);
        $delivery->update(['status' => 'approved']);
        return response()->json(['success' => true, 'message' => 'Delivery approved']);
    }

    // Reject
    public function reject($id)
    {
        $this->authorizeRole(['admin', 'project_manager']);

        $delivery = MaterialDelivery::findOrFail($id);
        $delivery->update(['status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'Delivery rejected']);
    }

    // Delete
    public function destroy($id)
    {
        $this->authorizeRole(['admin']);

        $delivery = MaterialDelivery::findOrFail($id);
        $delivery->delete();
        return response()->json(['success' => true, 'message' => 'Delivery deleted']);
    }

    // Utility for checking role
    private function authorizeRole(array $roles)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, $roles)) {
            abort(403, 'Access denied');
        }
    }
}

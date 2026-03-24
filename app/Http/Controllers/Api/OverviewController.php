<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OverviewController extends Controller
{
    public function getOverview(Request $request, $id)
    {
        if (! $id) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Not authorized to make this request!.',
                'data' => null,
            ], 404);
        }

        $inventoryStats = DB::table('inventory_requests')
            ->selectRaw("
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected,
            SUM(status = 'pending') as pending
        ")
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => DB::table('users')->count(),
                'projects' => DB::table('projects')->count(),
                'warehouses' => DB::table('warehouse_locations')->count(),
                'approved' => (int) ($inventoryStats->approved ?? 0),
                'rejected' => (int) ($inventoryStats->rejected ?? 0),
                'pending' => (int) ($inventoryStats->pending ?? 0),
            ],
        ]);
    }

    public function getOverviewManager(Request $request, $id)
    {
        if (! $id) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Not authorized to make this request!.',
                'data' => null,
            ], 404);
        }

        $inventoryStats = DB::table('inventory_requests')
            ->where('requested_by', $id)
            ->selectRaw("
            SUM(status = 'approved') as approved,
            SUM(status = 'rejected') as rejected,
            SUM(status = 'pending') as pending
        ")
            ->first();

        // Shipments via project_id (linked to manager's projects)
        $shipmentCount = DB::table('shipments as s')
            ->join('projects as p', 's.project_id', '=', 'p.id')
            ->where('p.manager_id', $id)
            ->where('s.status','in_transit')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'projects' => DB::table('projects')
                    ->where('manager_id', $id)
                    ->count(),

                'warehouses' => DB::table('warehouse_locations')->count(),

                'shipments' => $shipmentCount, // added

                'approved' => (int) ($inventoryStats->approved ?? 0),
                'rejected' => (int) ($inventoryStats->rejected ?? 0),
                'pending' => (int) ($inventoryStats->pending ?? 0),
            ],
        ]);
    }

    public function getOverviewWarehouse(Request $request, $id)
    {
        if (! $id) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Not authorized to make this request!.',
                'data' => null,
            ], 404);
        }

        // Get all warehouse IDs assigned to staff
        $warehouseIds = DB::table('warehouse_locations')
            ->where('staff_id', $id)
            ->pluck('id'); // returns array/collection of IDs

        $inventoryStats = DB::table('inventory_requests')
            ->whereIn('warehouse_id', $warehouseIds) // use whereIn
            ->selectRaw("
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'pending') as pending
            ")
            ->first();

        // Shipments via project_id (linked to manager's projects)
        $shipmentCount = DB::table('shipments as s')
            ->where('s.user_id', $id)
            ->where('s.status','in_transit')
            ->count();

        
        return response()->json([
            'success' => true,
            'data' => [
                'projects' => DB::table('projects')
                    ->count(),

                'warehouses' => DB::table('warehouse_locations')->count(),

                'shipments' => $shipmentCount, // added

                'approved' => (int) ($inventoryStats->approved ?? 0),
                'rejected' => (int) ($inventoryStats->rejected ?? 0),
                'pending' => (int) ($inventoryStats->pending ?? 0),
            ],
        ]);
    }
}

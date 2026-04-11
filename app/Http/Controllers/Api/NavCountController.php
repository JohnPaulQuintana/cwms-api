<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DefectItem;
use App\Models\Inventory;
use App\Models\InventoryRequest;
use App\Models\Project;
use App\Models\ReturnedInventory;
use App\Models\Shipment;
use App\Models\WarehouseLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NavCountController extends Controller
{
    // get nav count wiht role base
    public function index(Request $request, $role) {
        $low_stocks = 0;
        $pending_shipment = 0;
        $request = 0;
        $returned = 0;
        $defected = 0;
        $threshold = 5;
        $unassigned_warehouse = 0;
        $unassigned_projects = 0;
        $user = Auth::user();

        if ($user->role === "warehouse_staff") {

            $lowStocks = Inventory::where('quantity', '<=', $threshold)
                ->pluck('id');
            $low_stocks = $lowStocks->count();

            $pendingShipment = Shipment::where('status', 'pending')->where('user_id',$user->id)->pluck("id");
            $pending_shipment = $pendingShipment->count();

            $defected = DefectItem::whereDate('created_at', Carbon::today())
            ->count();

            $returned = ReturnedInventory::whereIn('status', ['pending','approved'])
            ->count();
            
            $warehouse_id = WarehouseLocation::where("staff_id", $user->id)->first();
            if ($warehouse_id) {
                $request = InventoryRequest::where('warehouse_id',$warehouse_id->id)
                ->where('status', 'pending')->count();
            }else{
                 $request = 0;
            }
        } else if ($user->role === "admin") {
            $lowStocks = Inventory::where('quantity', '<=', $threshold)
            ->pluck('id');

            $low_stocks = $lowStocks->count();

            $pending_shipment = Shipment::where('status', 'pending')
                ->count();

            $defected = DefectItem::whereDate('created_at', Carbon::today())
                ->count();

            $returned = ReturnedInventory::whereDate('created_at', Carbon::today())
                ->count();

            $request = InventoryRequest::where('status', 'pending')
                ->count();

            $unassigned_warehouse = WarehouseLocation::whereNull("staff_id")->count();

            $unassigned_projects = Project::whereNull("manager_id")->count();

        } else {
            $requestInventory = InventoryRequest::where('requested_by',$user->id)
                ->get();
            $request = $requestInventory->where('status', 'pending')->count();
            $projectIds = $requestInventory->pluck('project_id')->toArray();

            $pending_shipment = Shipment::whereIn('project_id', $projectIds)->where('status','pending')->count();
        }

        return response()->json([
            "status" => True,
            'inventory' => $low_stocks,
            'shipment' => $pending_shipment,
            'request' => $request,
            'returned' => $returned,
            'defected' => $defected,
            'warehouses' => $unassigned_warehouse,
            'projects' => $unassigned_projects
        ]);
    }
}

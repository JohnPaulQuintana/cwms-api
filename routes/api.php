<?php

use App\Http\Controllers\Api\DefectController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryRequestController;
use App\Http\Controllers\Api\MaterialDeliveryController;
use App\Http\Controllers\Api\OverviewController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\WarehouseLocationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public route
Route::post('/login', [UserManagementController::class, 'login']);

// Resend verification email
Route::post('/email/resend', [UserManagementController::class, 'resendVerificationEmail']);

// Email verification route (public)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify')
    ->middleware('signed');

// Authenticated routes
Route::group(['middleware' => 'auth:api'], function () {

    Route::get('/profile', [UserManagementController::class, 'profile']);
    Route::post('/logout', [UserManagementController::class, 'logout']);


    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/inventory/staff', [InventoryController::class, 'index_staff']);
    Route::get('/inventory/{id}', [InventoryController::class, 'show']);
    Route::post('/inventory', [InventoryController::class, 'store']);
    Route::post('/inventory/update/{id}', [InventoryController::class, 'update']);
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy']);

    // send an inventory request
    Route::post('/inventory/request', [InventoryRequestController::class, 'store']);
    Route::get('/inventory/request/status', [InventoryRequestController::class, 'index']);
    Route::delete('/inventory/request/{id}', [InventoryRequestController::class, 'destroy']);
    Route::post('/inventory/request/update/{id}', [InventoryRequestController::class, 'update']);


    Route::get('/locations', [WarehouseLocationController::class, 'index']);
    Route::get('/locations/staff', [WarehouseLocationController::class, 'inventory_staff']);
    Route::post('/locations', [WarehouseLocationController::class, 'store']);
    Route::post('/locations/update/{id}', [WarehouseLocationController::class, 'update']);
    Route::delete('/locations/{id}', [WarehouseLocationController::class, 'destroy']);

    //projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/my-projects', [ProjectController::class, 'show_my_projects']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::post('/projects/update/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // Deliveries
    Route::get('/deliveries', [MaterialDeliveryController::class, 'index']);
    Route::get('/deliveries/{id}', [MaterialDeliveryController::class, 'show']);
    Route::post('/deliveries', [MaterialDeliveryController::class, 'store']);
    Route::post('/deliveries/update/{id}', [MaterialDeliveryController::class, 'update']);
    Route::patch('/deliveries/{id}/approve', [MaterialDeliveryController::class, 'approve']);
    Route::patch('/deliveries/{id}/reject', [MaterialDeliveryController::class, 'reject']);
    Route::delete('/deliveries/{id}', [MaterialDeliveryController::class, 'destroy']);

    // users
    Route::get('/users/manager', [UserManagementController::class, 'manager']);


    //staff routes
    Route::get('/users/staff/requests', [InventoryRequestController::class, 'getInventoryRequestDetails']);
    Route::post('/users/inventory-requests/{id}/action', [InventoryRequestController::class, 'handleAction']);


    //Shipment routes
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::post('/shipments', [ShipmentController::class, 'store']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
    Route::patch('/shipments/{id}/status', [ShipmentController::class, 'updateStatus']);

    //defect items routes
    Route::get('/users/defect-items', [DefectController::class, 'getDefectItems']);
    Route::post('/users/defect-items', [DefectController::class, 'addDefectItem']);

    // overview for manager and staff
    // get the overview
    Route::get('/overviewManager/{id}', [OverviewController::class, 'getOverviewManager']);
    Route::get('/overviewWarehouse/{id}', [OverviewController::class, 'getOverviewWarehouse']);

    // Admin-only routes
    Route::group(['middleware' => 'role:admin'], function () {
        Route::post('/register', [UserManagementController::class, 'register']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::get('/users/staff', [UserManagementController::class, 'staff']);
        Route::get('/users/{id}', [UserManagementController::class, 'show']);
        Route::post('/users/{id}', [UserManagementController::class, 'update']);
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);

        // Get warehouse records
        Route::get('/locations/{id}/records', [WarehouseLocationController::class, 'getWarehouseRecords']);

        // get the overview
        Route::get('/overview/{id}', [OverviewController::class, 'getOverview']);
    });
});

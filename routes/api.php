<?php

use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MaterialDeliveryController;
use App\Http\Controllers\Api\ProjectController;
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


    Route::get('/inventory', [InventoryController::class,'index']);
    Route::get('/inventory/staff', [InventoryController::class,'index_staff']);
    Route::get('/inventory/{id}', [InventoryController::class,'show']);
    Route::post('/inventory', [InventoryController::class,'store']);
    Route::post('/inventory/update/{id}', [InventoryController::class,'update']);
    Route::delete('/inventory/{id}', [InventoryController::class,'destroy']);

    Route::get('/locations', [WarehouseLocationController::class,'index']);
    Route::get('/locations/staff', [WarehouseLocationController::class,'inventory_staff']);
    Route::post('/locations', [WarehouseLocationController::class,'store']);
    Route::post('/locations/update/{id}', [WarehouseLocationController::class,'update']);
    Route::delete('/locations/{id}', [WarehouseLocationController::class,'destroy']);

    //projects
    Route::get('/projects', [ProjectController::class, 'index']);
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

    // Admin-only routes
    Route::group(['middleware' => 'role:admin'], function () {
        Route::post('/register', [UserManagementController::class, 'register']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::get('/users/manager', [UserManagementController::class, 'manager']);
        Route::get('/users/staff', [UserManagementController::class, 'staff']);
        Route::get('/users/{id}', [UserManagementController::class, 'show']);
        Route::post('/users/{id}', [UserManagementController::class, 'update']);
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);
    });
});

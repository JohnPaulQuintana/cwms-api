<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'warehouse_id',
        'requested_qty',
        'requested_by',
        'status',
        'project_id',
        'rejection_reason',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function shipmentItem()
    {
        return $this->hasOne(ShipmentItem::class);
    }

    public function returns()
    {
        return $this->hasMany(ReturnedInventory::class, 'inventory_request_id');
    }
}

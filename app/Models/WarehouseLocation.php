<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'address', 'staff_id'];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    //belongs to staff (user)
    public function staff(){
        return $this->belongsTo(User::class, 'staff_id');
    }

    //relationship  with inventory requests
    public function inventoryRequests(){
        return $this->hasMany(InventoryRequest::class, 'warehouse_id');
    }
}


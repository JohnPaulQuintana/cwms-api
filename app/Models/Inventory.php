<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sku', 'description', 'quantity', 'reorder_quantity', 'unit', 'location_id'
    ];

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'location_id');
    }

    public function defectItems()
    {
        return $this->hasMany(DefectItem::class, 'inventory_id');
    }

    public function reorders()
    {
        return $this->hasMany(InventoryReorder::class, 'inventory_id');
    }
}

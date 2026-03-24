<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
     protected $fillable = [
        'shipment_id',
        'inventory_request_id',
        'quantity',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function inventoryRequest()
    {
        return $this->belongsTo(InventoryRequest::class, 'inventory_request_id');
    }
}

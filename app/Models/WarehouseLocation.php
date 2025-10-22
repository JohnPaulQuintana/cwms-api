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
}


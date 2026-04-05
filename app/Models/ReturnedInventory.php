<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReturnedInventory extends Model
{
    use HasFactory;

    protected $table = 'returned_inventory';

    protected $fillable = [
        'inventory_request_id',
        'inventory_name',
        'project_id',
        'warehouse_name',
        'quantity',
        'unit',
        'status'
    ];
}

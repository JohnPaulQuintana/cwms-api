<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id', 'quantity', 'delivery_type',
        'project_id', 'delivered_by', 'status', 'delivery_date'
    ];

    public function inventory() {
        return $this->belongsTo(Inventory::class);
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function deliveredBy() {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}

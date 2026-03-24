<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'tracking_number',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function defectItems()
    {
        return $this->hasMany(DefectItem::class, 'shipment_id');
    }

}

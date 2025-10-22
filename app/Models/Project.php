<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'manager_id', 'start_date', 'end_date'];

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function deliveries() {
        return $this->hasMany(MaterialDelivery::class);
    }
}

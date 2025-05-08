<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'duration_phase_1',
        'rest_duration',
        'duration_phase_2',
    ];

    public function serviceWithHairlengths()
    {
        return $this->hasMany(ServiceWithHairlength::class);
    }
}

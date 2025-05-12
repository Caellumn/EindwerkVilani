<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gender',
    ];

    /**
 * Get the availability blocks for this agenda
 */
public function availabilityBlocks()
{
    return $this->hasMany(AvailabilityBlock::class);
}

}

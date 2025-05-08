<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceWithHairlength extends Model
{
    // /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    //explicit say the table name to look for
    protected $table = 'serviceswithhairlengths';

    protected $fillable = [
        'service_id',
        'hairlength_id',
        'price'
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function hairlength(): BelongsTo
    {
        return $this->belongsTo(Hairlength::class);
    }
}

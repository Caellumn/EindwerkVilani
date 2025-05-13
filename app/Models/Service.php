<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'hairlength',
        'price',
        'active'
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
            // ->withPivot('active')
            // ->withTimestamps()
            // ->where('category_service.active', 1);
    }

}

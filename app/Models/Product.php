<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'stock', 'image', 'active'];
    
    public function categories(): BelongsToMany
{
    return $this->belongsToMany(Category::class)
        ->withPivot('active')
        ->withTimestamps()
        ->wherePivot('active', 1);
}

}

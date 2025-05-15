<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    //
    protected $fillable = ['name', 'active'];
   
    public function products(): BelongsToMany
{
    return $this->belongsToMany(Product::class)
        ->withPivot('active')
        ->withTimestamps()
        ->where('category_product.active', 1);
}

public function services(): BelongsToMany
{
    return $this->belongsToMany(Service::class)
        ->withPivot('active')
        ->withTimestamps()
        ->where('category_service.active', 1);
}

}

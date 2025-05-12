<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    //
    protected $fillable = ['name', 'active'];
   
    public function products()
{
    return $this->belongsToMany(Product::class)
        ->withPivot('active')
        ->withTimestamps()
        ->wherePivot('active', 1);
}

public function services()
{
    return $this->belongsToMany(Service::class)
        ->withPivot('active')
        ->withTimestamps()
        ->where('category_service.active', 1);
}

}

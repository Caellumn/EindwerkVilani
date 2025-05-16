<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Category extends Model
{
    use HasUuids;
    
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';
    
    protected $fillable = ['name', 'active'];
   
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('active')
            ->withTimestamps()
            ->using(CategoryProduct::class)
            ->where('category_product.active', 1);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('active')
            ->withTimestamps()
            ->using(CategoryService::class)
            ->where('category_service.active', 1);
    }
}

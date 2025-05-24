<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
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

    protected $fillable = ['name', 'description', 'price', 'stock', 'image', 'active'];
    
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($product) {
            // Handle image upload to Cloudinary when image field is being saved
            if ($product->isDirty('image') && $product->image && !str_starts_with($product->image, 'http')) {
                // Check if it's a file path (not already a URL)
                $imagePath = Storage::disk('public')->path($product->image);
                
                if (file_exists($imagePath)) {
                    try {
                        // Upload to Cloudinary
                        $uploadedFile = Cloudinary::upload($imagePath, [
                            'folder' => 'products',
                            'resource_type' => 'image'
                        ]);
                        
                        // Replace local path with Cloudinary URL
                        $product->image = $uploadedFile->getSecurePath();
                        
                        // Optionally delete the local file
                        Storage::disk('public')->delete($product->getOriginal('image'));
                    } catch (\Exception $e) {
                        // Log error but don't break the save process
                        Log::error('Cloudinary upload failed: ' . $e->getMessage());
                    }
                }
            }
        });
    }
    
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withPivot('active')
            ->withTimestamps()
            ->using(CategoryProduct::class)
            ->wherePivot('active', 1);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class)
                    ->using(BookingProduct::class);
    }
}

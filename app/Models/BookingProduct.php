<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BookingProduct extends Model
{
     //

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
     
     /**
      * The table associated with the model.
      *
      * @var string
      */
     protected $table = 'booking_product';
}

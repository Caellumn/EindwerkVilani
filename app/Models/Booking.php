<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    //has uuid
    use HasUuids;

    protected $fillable = ['date', 'name', 'email', 'telephone', 'gender', 'remarks', 'status', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

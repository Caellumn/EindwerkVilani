<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Hairlength extends Model
{
    //explicit say the table name to look for
    protected $table = 'hairlengths';

    protected $fillable = ['length'];
}

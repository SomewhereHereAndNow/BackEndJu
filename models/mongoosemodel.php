<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define the table name if it's different from the pluralized model name
    // protected $table = 'products';

    // Define fillable properties
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'image',
    ];
}

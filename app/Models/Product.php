<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'price',
        'img',
        'active',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }
}

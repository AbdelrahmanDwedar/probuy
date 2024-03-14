<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Category extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function product(): Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }
}

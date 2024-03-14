<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function categories(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function clicks(): Relations\HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function tags(): Relations\HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function orderItems(): Relations\BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class);
    }
}

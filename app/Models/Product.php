<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Category, \App\Models\Product>
     */
    public function categories(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Click>
     */
    public function clicks(): Relations\HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Tag>
     */
    public function tags(): Relations\HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\OrderItem>
     */
    public function orderItems(): Relations\BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class);
    }
}

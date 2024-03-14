<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function products(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}

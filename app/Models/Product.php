<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use BelongsToTenant, HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'sku',
        'title',
        'description',
        'status',
        'metadata',
        'published_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->approved();
    }

    // Update average rating
    public function updateAverageRating(): void
    {
        $stats = $this->reviews()->approved()->selectRaw('AVG(rating) as average, COUNT(*) as count')->first();
        
        $this->update([
            'average_rating' => $stats->average ? round($stats->average, 2) : null,
            'review_count' => $stats->count ?? 0,
        ]);
    }

    // Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('tenant_media');

        $this->addMediaCollection('documents')
            ->useDisk('tenant_media');
    }

    // Status methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    // Business logic
    public function publish(): void
    {
        if ($this->variants()->count() === 0) {
            throw new \DomainException('Product must have at least one variant to be published');
        }

        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        event(new \App\Events\ProductPublished($this));
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
        event(new \App\Events\ProductArchived($this));
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}


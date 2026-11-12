<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MarketingPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $published_at
 */
class MarketingPage extends Model
{
    /** @use HasFactory<MarketingPageFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'slug',
        'page_type',
        'title',
        'excerpt',
        'content_html',
        'seo_title',
        'seo_description',
        'sort_order',
        'published_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}

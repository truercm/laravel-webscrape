<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlSubject as Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrawlSubject extends Model implements Contract
{
    use HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'credentials',
        'authenticated_at',
    ];

    /** @var string[] */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'authenticated_at' => 'datetime',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getTargetUrls(): Collection
    {
        return $this->crawlTarget
            ->crawlTargetUrls
            ->map(fn(CrawlTargetUrl $crawlTargetUrl) => $crawlTargetUrl->setAttribute('url', $crawlTargetUrl->url_template));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crawlResults(): HasMany
    {
        return $this->hasMany(CrawlResult::class);
    }
}

<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject as Contract;

class CrawlSubject extends Model implements Contract
{
    use Concerns\HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'credentials',
        'authenticated_at',
        'result',
    ];

    /** @var string[] */
    protected function casts(): array
    {
        return [
            'credentials' => 'array',
            'authenticated_at' => 'datetime',
            'result' => 'array',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(config('webscrape.models.target'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function targetUrls(): HasMany
    {
        return $this->hasMany(config('webscrape.models.target_url'), 'crawl_target_id', 'crawl_target_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crawlResults(): HasMany
    {
        return $this->hasMany(config('webscrape.models.result'));
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getTargetUrls(): Collection
    {
        return $this->targetUrls
            ->map(fn (CrawlTargetUrl $crawlTargetUrl) => $crawlTargetUrl->setAttribute('url', $crawlTargetUrl->url_template));
    }
}

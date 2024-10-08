<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    protected $casts = [
        'credentials' => 'array',
        'authenticated_at' => 'datetime',
        'result' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
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
}

<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlTarget as Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CrawlTarget extends Model implements Contract
{
    use HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'auth_url',
        'crawling_job',
    ];

    /** @var string[] */
    protected function casts(): array
    {
        return [

        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function crawlSubject(): HasOne
    {
        return $this->hasOne(CrawlSubject::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crawlTargetUrls(): HasMany
    {
        return $this->hasMany(CrawlTargetUrl::class);
    }
}

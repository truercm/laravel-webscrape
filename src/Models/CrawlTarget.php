<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTarget as Contract;

class CrawlTarget extends Model implements Contract
{
    use Concerns\HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'auth_url',
        'auth_button_text',
        'crawling_job',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function crawlSubject(): HasOne
    {
        return $this->hasOne(config('webscrape.models.subject'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function crawlTargetUrls(): HasMany
    {
        return $this->hasMany(config('webscrape.models.target_url'));
    }
}

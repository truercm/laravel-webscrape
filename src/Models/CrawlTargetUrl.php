<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl as Contract;

class CrawlTargetUrl extends Model implements Contract
{
    use Concerns\HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'url_template',
        'handler',
        'result_fields',
    ];

    /** @var string[] */
    protected $casts = [
        'result_fields' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(config('webscrape.models.target'));
    }
}

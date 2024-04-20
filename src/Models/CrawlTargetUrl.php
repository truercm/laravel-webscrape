<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlTargetUrl as Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrawlTargetUrl extends Model implements Contract
{
    use HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'url_template',
        'handler',
    ];

    /** @var string[] */
    protected function casts(): array
    {
        return [

        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class);
    }
}

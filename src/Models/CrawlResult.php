<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult as Contract;

class CrawlResult extends Model implements Contract
{
    use Concerns\HasFactory;
    use SoftDeletes;

    /** @var string[] */
    protected $fillable = [
        'url',
        'handler',
        'status',
        'body',
        'processed_at',
        'process_status',
        'result',
        'creator',
    ];

    /** @var string[] */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'result' => 'array',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlSubject(): BelongsTo
    {
        return $this->belongsTo(config('webscrape.models.subject'));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTargetUrl(): BelongsTo
    {
        return $this->belongsTo(config('webscrape.models.target_url'));
    }
}

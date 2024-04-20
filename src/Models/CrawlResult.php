<?php

namespace TrueRcm\LaravelWebscrape\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TrueRcm\LaravelWebscrape\Models\Contracts\CrawlResult as Contract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrawlResult extends Model implements Contract
{
    use HasFactory;
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
        return $this->belongsTo(CrawlSubject::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crawlTargetUrl(): BelongsTo
    {
        return $this->belongsTo(CrawlTargetUrl::class);
    }
}

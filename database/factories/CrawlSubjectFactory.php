<?php

namespace TrueRcm\LaravelWebscrape\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TrueRcm\LaravelWebscrape\Models\CrawlSubject>
 */
class CrawlSubjectFactory extends Factory
{
    protected $model = CrawlSubject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model_type' => 'App\Models\User',
            'model_id' => 1,
            'crawl_target_id' => CrawlTarget::factory(),
            'credentials' => [],
            'authenticated_at' => null,
        ];
    }
}

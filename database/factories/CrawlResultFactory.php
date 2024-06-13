<?php

namespace TrueRcm\LaravelWebscrape\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrueRcm\LaravelWebscrape\Models\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TrueRcm\LaravelWebscrape\Models\CrawlResult>
 */
class CrawlResultFactory extends Factory
{
    protected $model = CrawlResult::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crawl_target_url_id' => CrawlTargetUrl::factory(),
            'crawl_subject_id' => CrawlSubject::factory(),
            'url' => $this->faker->url(),
            'handler' => $this->faker->word(),
            'status' => $this->faker->randomDigit(),
            'body' => $this->faker->optional()->sentence(),
            'processed_at' => $this->faker->optional()->dateTime(),
            'process_status' => 'pending',
            'result' => null,
        ];
    }
}

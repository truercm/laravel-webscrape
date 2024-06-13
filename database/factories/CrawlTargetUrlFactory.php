<?php

namespace TrueRcm\LaravelWebscrape\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl>
 */
class CrawlTargetUrlFactory extends Factory
{
    protected $model = CrawlTargetUrl::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crawl_target_id' => CrawlTarget::factory(),
            'url_template' => $this->faker->url(),
            'handler' => $this->faker->word(),
        ];
    }
}

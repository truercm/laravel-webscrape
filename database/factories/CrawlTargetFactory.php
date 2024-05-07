<?php

namespace TrueRcm\LaravelWebscrape\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TrueRcm\LaravelWebscrape\Models\CrawlTarget;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TrueRcm\LaravelWebscrape\Models\CrawlTarget>
 */
class CrawlTargetFactory extends Factory
{
    protected $model = CrawlTarget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auth_url' => fake()->url(),
            'auth_button_text' => 'Login',
            'crawling_job' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        ];
    }
}

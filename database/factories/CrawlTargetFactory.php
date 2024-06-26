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
            'url' => $this->faker->url(),
            'name' => $this->faker->company(),
            'auth_url' => $this->faker->url(),
            'auth_button_text' => 'Login',
            'crawling_job' => '\TrueRcm\LaravelWebscrape\Tests\Fixtures\FixtureJob',
        ];
    }
}

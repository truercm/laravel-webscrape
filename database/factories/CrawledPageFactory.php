<?php

namespace TrueRcm\LaravelWebscrape\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\TrueRcm\LaravelWebscrape\Models\CrawledPage>
 */
class CrawledPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_id'=> $this->faker->randomDigit(),
            'url' => $this->faker->url(),
            'handler' => $this->faker->word(),
            'status' => $this->faker->randomDigit(),
            'body' => $this->faker->optional()->sentence(),
            'processed_at'=> $this->faker->optional()->dateTime(),
            'process_status' => $this->faker->word(),
            'result'=> $this->faker->optional()->shuffleArray(),
        ];
    }
}

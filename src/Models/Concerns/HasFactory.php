<?php

namespace TrueRcm\LaravelWebscrape\Models\Concerns;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory as BaseHasFactory;

trait HasFactory
{
    use BaseHasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        $factory = sprintf('TrueRcm\LaravelWebscrape\Database\Factories\%sFactory', class_basename(get_called_class()));

        return $factory::new();
    }
}

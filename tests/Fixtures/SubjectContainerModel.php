<?php

namespace TrueRcm\LaravelWebscrape\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TrueRcm\LaravelWebscrape\Contracts\HasWebscrapes;
use TrueRcm\LaravelWebscrape\Models\Concerns\InteractsWithHasWebscrapes;

class SubjectContainerModel extends Model implements HasWebscrapes
{
    use HasFactory;
    use InteractsWithHasWebscrapes;

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        $factory = new class() extends Factory {
            /** @var class-string */
            protected $model = SubjectContainerModel::class;

            /**
             * @return array
             */
            public function definition()
            {
                return [];
            }
        };

        return $factory::new();
    }

    /**
     * @return \Closure
     */
    public static function schema()
    {
        return function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->temporary();
        };
    }

    /**
     * @return void
     */
    public static function migrate()
    {
        Schema::create(static::make()->getTable(), static::schema());
    }
}

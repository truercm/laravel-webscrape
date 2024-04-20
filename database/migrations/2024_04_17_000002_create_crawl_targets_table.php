<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlTargetsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('crawl_targets', static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('auth_url');
                $table->string('crawling_job');

                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_targets');
    }
}

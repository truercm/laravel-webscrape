<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlTargetUrlsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('crawl_target_urls', static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('crawl_target_id')->index();
                $table->string('url_template');
                $table->string('handler')->index();

                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_target_urls');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlResultsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('crawl_results', static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('crawl_target_url_id')->index();
                $table->unsignedBigInteger('crawl_subject_id')->index();
                $table->string('url');
                $table->string('handler');
                $table->integer('status');
                $table->mediumText('body')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->string('process_status')->index();
                $table->json('result')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_results');
    }
}

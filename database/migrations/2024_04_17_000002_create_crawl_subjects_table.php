<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrawlSubjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('crawl_subjects', static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('crawl_target_id')->index();
                $table->json('credentials')->nullable();
                $table->dateTime('authenticated_at')->nullable();

                $table->timestamps();
                $table->softDeletes();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_subjects');
    }
}

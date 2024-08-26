<?php

namespace TrueRcm\LaravelWebscrape\Tests\Feature\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use TrueRcm\LaravelWebscrape\Tests\Fixtures\SubjectContainerModel;
use TrueRcm\LaravelWebscrape\Tests\TestCase;

class InteractsWithHasWebscrapesTest extends TestCase
{
    /** @return void */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_will_check_function_implementation_in_subject_container_model(): void
    {
        $hasWebscrapes = new SubjectContainerModel();

        $this->assertInstanceOf(MorphMany::class, $hasWebscrapes->crawlSubjects());
        $this->assertEquals([], $hasWebscrapes->crawlCredentials());
    }
}

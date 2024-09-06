<?php

namespace TrueRcm\LaravelWebscrape\Tests\Feature\Pipes;

use Mockery\MockInterface;
use TrueRcm\LaravelWebscrape\CrawlTraveller;
use TrueRcm\LaravelWebscrape\Pipes\CloseBrowser;
use TrueRcm\LaravelWebscrape\Tests\TestCase;

class CloseBrowserTest extends TestCase
{
    /** @test */
    public function it_will_handle_closing_browser(): void
    {
        $stub = $this->mock(CrawlTraveller::class, function (MockInterface $mock)  {
                    $mock->expects('clearBrowser')
                        ->once()
                        ->andReturnSelf();
                });

    app(CloseBrowser::class)
        ->handle($stub, function (CrawlTraveller $traveller) use ($stub) {
            $this->assertSame($stub, $traveller);
        });
    }
}

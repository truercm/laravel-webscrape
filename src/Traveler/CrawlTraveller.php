<?php

namespace TrueRcm\LaravelWebscrape\Traveler;

use Illuminate\Support\Collection;
use Symfony\Component\BrowserKit\HttpBrowser;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Models\CrawlSubject;
use TrueRcm\LaravelWebscrape\Models\CrawlTargetUrl;

class CrawlTraveller
{
    protected CrawlSubject $subject;
    protected ?HttpBrowser $browser=null;

    protected array $crawledPages = [];

    public function __construct(CrawlSubject $subject)
    {
        $this->subject = $subject;
    }

    /**
     * @param \TrueRcm\LaravelWebscrape\Contracts\CrawlSubject $subject
     * @return $this
     */
    public static function make(CrawlSubject $subject): self
    {
        return new static($subject);
    }

    public function subject(): CrawlSubject
    {
        return $this->subject;
    }

    public function getBrowser(): HttpBrowser
    {
        return $this->browser;
    }

    public function setBrowser(HttpBrowser $bowser): self
    {
        $this->browser = $bowser;

        return $this;
    }

    public function targets(): Collection
    {
        return $this->subject
           ->targetUrls
            ->map(fn(CrawlTargetUrl $crawlTargetUrl) => $crawlTargetUrl->setAttribute('url', $crawlTargetUrl->url_template));
    }

    public function authUrl(): ?string
    {
        return $this->subject
            ->crawlTarget
            ->auth_url ?? null;
    }

    public function authButtonIdentifier(): ?string
    {
        return $this->subject
            ->crawlTarget
            ->auth_button_text ?? null;
    }

    public function getCrawlingCredentials(): array
    {
        return json_decode($this->subject->credentials, true);
    }

    public function addCrawledPage(CrawlResult $page): self
    {
        $this->crawledPages[] = $page;

        return $this;
    }

    public function getCrawledPages(): Collection
    {
        return collect($this->crawledPages);
    }
}

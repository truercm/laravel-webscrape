<?php

namespace TrueRcm\LaravelWebscrape;

use Illuminate\Support\Collection;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;
use TrueRcm\LaravelWebscrape\Contracts\CrawlResult;
use TrueRcm\LaravelWebscrape\Contracts\CrawlSubject;
use TrueRcm\LaravelWebscrape\Contracts\CrawlTargetUrl;
use TrueRcm\LaravelWebscrape\Exceptions\CrawlException;
use TrueRcm\LaravelWebscrape\Jobs\CrawlTargetJob;

/** @phpstan-consistent-constructor */
class CrawlTraveller
{
    /**
     * An implementation for the browser client.
     * @var \TrueRcm\LaravelWebscrape\Contracts\BrowserClient|null
     */
    protected ?BrowserClient $browser = null;

    /** @var \TrueRcm\LaravelWebscrape\Contracts\CrawlResult[] */
    protected array $crawledPages = [];

    /**
     * Create new CrawlTraveller.
     */
    final public function __construct(
        protected CrawlSubject $subject
    ) {
    }

    /**
     * Statically create a new CrawlTraveller.
     */
    public static function make(CrawlSubject $subject): static
    {
        return new static($subject);
    }

    /**
     * Begin crawling.
     */
    public function start(): void
    {
        CrawlTargetJob::dispatch($this);
    }

    /**
     * Get the subject of the CrawlTraveller.
     */
    public function subject(): CrawlSubject
    {
        return $this->subject;
    }

    /**
     * Get the BrowserClient.
     */
    public function getBrowser(): BrowserClient
    {
        throw_if(null === $this->browser, CrawlException::noBrowserSetUp($this));

        return $this->browser;
    }

    /**
     * Set the BrowserClient.
     */
    public function setBrowser(BrowserClient $bowser): self
    {
        $this->browser = $bowser;

        return $this;
    }

    /**
     * Clear the browser on traveller
     */
    public function clearBrowser(): self
    {
        $this->browser->quit();

        $this->browser = null;

        return $this;
    }

    /**
     * Collect targets to crawl.
     */
    public function targets(): Collection
    {
        return $this->subject
            ->loadMissing('targetUrls')
            ->getRelation('targetUrls')
            ->map(fn (CrawlTargetUrl $crawlTargetUrl) => $crawlTargetUrl->setAttribute('url', $crawlTargetUrl->url_template));
    }

    /**
     * Get authentication URL.
     */
    public function authUrl(): ?string
    {
        return $this->subject
            ->loadMissing('crawlTarget')
            ->getRelation('crawlTarget')
            ->auth_url ?? null;
    }

    /*
     * Resolve the identifier for the login button
     */
    public function authButtonIdentifier(): ?string
    {
        return $this->subject
            ->crawlTarget
            ->auth_button_text ?? null;
    }

    /**
     * get crawling credentials.
     */
    public function getCrawlingCredentials(): array
    {
        return $this->subject->credentials;
    }

    /**
     * Add anotehr crawl result.
     */
    public function addCrawledPage(CrawlResult $page): self
    {
        $this->crawledPages[] = $page;

        return $this;
    }

    /**
     * Get crawled pages.
     */
    public function getCrawledPages(): Collection
    {
        return collect($this->crawledPages);
    }
}

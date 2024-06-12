<?php

namespace TrueRcm\LaravelWebscrape;

use Illuminate\Support\Traits\ForwardsCalls;
use Symfony\Component\Panther\Client;
use TrueRcm\LaravelWebscrape\Contracts\BrowserClient;

class Browser implements BrowserClient
{
    use ForwardsCalls;

    /**
     * @param \Symfony\Component\Panther\Client $client
     */
    public function __construct(
        protected Client $client
    ) {
    }

    /**
     * Dynamically pass missing methods to the Symfony instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardDecoratedCallTo($this->client, $method, $parameters);
    }
}

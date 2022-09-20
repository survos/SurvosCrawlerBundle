<?php

namespace Survos\CrawlerBundle\Services;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;


class CrawlerClient extends KernelBrowser
{

    public function __construct(KernelInterface $kernel, array $server = [], History $history = null, CookieJar $cookieJar = null)
    {
        parent::__construct($kernel, $server, $history, $cookieJar);
    }

    /**
     * @inheritDoc
     */
    protected function doRequest(object $request): Response
    {
//        assert(false);
        // TODO: Implement doRequest() method.
        $response = parent::doRequest($request);
        return $response;
    }
}

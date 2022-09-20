<?php

namespace Survos\CrawlerBundle\Services;

use Doctrine\Persistence\ManagerRegistry;
use Goutte\Client;
use Psr\Log\LoggerInterface;
use Survos\CrawlerBundle\Model\Link;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

class CrawlerService
{
    private ?Client $goutteClient = null;

    public function __construct(
        private array $config,
        private string $baseUrl,
        private string $userClass,
        private string $loginPath,
        private string $submitButtonSelector,
        private string $plaintextPassword,
        private string $initialPath,
        /* @todo: User Provider */
        private ManagerRegistry $managerRegistry,
        //        private CrawlerClient       $client,
        private RouterInterface $router,
        //        private Client $gouteClient,
        //        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private array $linkList = [],
        private ?string $username = null,
    ) {
        //        $this->baseUrl = 'https://127.0.0.1:8001';
    }

    public function getUserClass(): string
    {
        return $this->$this->userClass;
    }


    public function getInitialPath(): string
    {
        return $this->initialPath;
    }

    public function getUsers()
    {
        return $this->managerRegistry->getRepository($this->userClass)->findBy([]);
    }

    private function linkListKey(?string $username, string $path): string
    {
        return sprintf('%s@%s', $username, $path);
    }

    public function addLink(?string $username, string $path, ?string $foundOn = null): Link
    {
        //        $key = $this->linkListKey($username, $path);

        if (! array_key_exists($username, $this->linkList)) {
            $this->linkList[$username] = [];
        }
        if (! array_key_exists($path, $this->linkList[$username])) {
            $this->linkList[$username][$path] = new Link(username: $username, path: $path, foundOn: $foundOn);
        }
        $link = $this->linkList[$username][$path];
        return $link;
    }

    public function getLinkList(?string $username): array
    {
        return $this->linkList[$username];
    }

    public function getEntireLinkList(): array
    {
        return $this->linkList;
    }

    public function resetLinkList(): void
    {
        $this->linkList = [];
    }

    public function getPendingLinks(?string $username): array
    {
        return array_filter($this->getLinkList($username), fn (Link $link) => $link->isPending());
    }

    public function getUnvisitedLink(?string $username): ?Link
    {
        return current($this->getPendingLinks($username)) ?: null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    // set the goutte client to an authenticated user
    public function authenticateClient(?string $username = null, string $plainPassword = null): void
    {
        // might be worth checking out: https://github.com/liip/LiipTestFixturesBundle/pull/62#issuecomment-622191412
        static $clients = [];
        if (! array_key_exists($username, $clients)) {
            $gouteClient = new Client();
            $gouteClient
                ->setMaxRedirects(0);
            $this->username = $username;
            $baseUrl = $this->baseUrl;
            $clients[$username] = $gouteClient;

            if ($username) {
                $crawler = $gouteClient->request('GET', $url = $baseUrl . trim($this->loginPath, '/'), [
                    'proxy' => '127.0.0.1:7080',
                ]);

                //            dd($crawler, $url);
                $response = $gouteClient->getResponse();
                assert($response->getStatusCode() === 200, "Invalid route: " . $url);
                //            dd(substr($response->getContent(),0, 1024), $url, $baseUrl);

                // select the form and fill in some values
                //                $form = $crawler->filter('login_form')->form();
                $form = $crawler->selectButton('login_button')->form();
                assert($form, "login_form is not found");
                try {
                    //                    $loginCrawler = new Crawler($response->getContent(), $url);
                    //                    $form = $loginCrawler->form(); // first form on the page?
                    //                    $form = $loginCrawler->selectButton($this->submitButtonSelector);//->form();
                    //                    dd($form);
                } catch (\Exception $exception) {
                    echo $response->getContent();
                    throw new \Exception($this->submitButtonSelector . ' does not find a form on ' . $this->loginPath);
                }
//                assert($form, $this->submitButtonSelector . ' does not find a form on ' . $this->loginPath);
//                dd($this->config, $form->getValues());
                $form[$this->config['username_form_variable']] = $username;
                $form[$this->config['password_form_variable']] = $plainPassword;

                // submit that form
                $crawler = $gouteClient->submit($form);
                $response = $gouteClient->getResponse();
                assert($response->getStatusCode() == 200, substr($response->getContent(), 0, 512) . "\n\n" . $url);

                //            dd($response);
                //                $crawler = $gouteClient->request('GET', $uri = "$baseUrl/project");
                //                $response = $gouteClient->getResponse();
                //                assert($response->getStatusCode() == 200, "Invalid link " . $uri);
            }
            $this->goutteClient = $clients[$username];
            return;

            // @todo: user provider instead of doctrine
            $user = $this->managerRegistry->getRepository($this->userClass)->findOneBy([
                'email' => $username,
            ]);
            assert($user, "Invalid user $username, not in user database");

            $uri = 'http://jardin.wip/login_check';
            $uri = '/login';

            $response = $this->httpClient->request('POST', $uri, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => 'tt@survos.com',
                    'password' => 'ttx',
                ],
            ]);
            $statusCode = $response->getStatusCode();

            $content = $response->getContent();
            dd($content, $response, $statusCode);
            if ($statusCode == 200) {
            }

            //            $this->client->loginUser($user);
            // @todo: configure
            $formData = new FormDataPart($formFields);
            $client->request('POST', 'https://...', [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);
        }
        //        $this->router = $container->get('router');
        //        $this->cache = $container->get('cache.app');
    }

    private function getKey($path): string
    {
        return sprintf("%s-%s", $this->username, $path);
    }

    public function scrape(Link $link, int $depth = 0): void
    {
        //        $this->logger->info("Scraping " . $link->getPath());
        $link->setSeen(true);

        if ($depth && ($link->getDepth() > $depth)) {
            return;
        }

        $this->setRoute($link);
        $routesToIgnore = ['app_logout'];
        if (in_array($link->getRoute(), $routesToIgnore)) {
            $link->setLinkStatus($link::STATUS_IGNORED);
            return;
        }

        if ($link->getLinkStatus() === $link::STATUS_IGNORED) {
            return;
        }

        // ugh, sloppy
        $url = trim($this->baseUrl, '/') . '/' . trim($link->getPath(), '/');
        assert(is_string($url));
        assert(parse_url($url), "Invalid url: " . $url);
        //        $response = $this->cache->get(md5($url), function(CacheItem $item) use ($url, $info, $path) {

        $crawler = $this->goutteClient->request('GET', $url);

        $response = $this->goutteClient->getResponse();

        //        dd($response->getStatusCode(), $request, $this->goutteClient);
        $status = $response->getStatusCode();
        $link
//            ->setDuration($response->getInfo('total_time'))
            ->setStatusCode($status);

        if ($status <> 200) {
            // @todo: what should we do here?
            $this->logger->error("Error scraping $url: " . $status);
            //            dd($response->getStatusCode(), $this->baseUrl . $link->getPath());
            $html = false;
        } else {
            $html = $response->getContent();
        }
        assert($status === 200, $link->username . '@' . $this->baseUrl . trim($link->getPath(), '/') . ' ' . $link->getRoute() . ' caused a ' . $status . ' found on ' . $link->foundOn);

        //        $responseInfo = $response->getInfo();
        //        unset($responseInfo['pause_handler']);
        //            dd($responseInfo);

        //        assert(array_key_exists('info', $info));
        //        $crawler = $client->request('GET',  $base . $path);
        if (! $html) {
            return;
        }

        $crawler = new Crawler($html, $url);
        //        $crawler = new \Symfony\Component\DomCrawler\Crawler()
        // Get the latest post in this category and display the titles
        $crawler->filter(' a')->each(
            function ($node) use ($link, $depth) {
                $href = (string) $node->attr('href');
                $cleanHref = str_replace($this->baseUrl, '', $href);
                $cleanHref = u($cleanHref)->before('#')->toString();
                if (empty($cleanHref)) {
                    return null;
                }
                if (preg_match('{^/(_(profiler|wdt)|css|images|js)/}', $cleanHref)) {
                    //                dd($href);
                    return null;
                }
                $parts = parse_url($cleanHref);
                if (preg_match('/http/', $cleanHref)) {
                    if ($parts['host'] ?? false) {
                        return null;
                    }
                }
                $pageLink = $this->addLink($link->username, $cleanHref, foundOn: $link->getPath());
                $pageLink->setDepth($depth + 1);
            }
        );
    }

    private function cleanup(string $href): ?string
    {
        $cleanHref = str_replace($this->baseUrl, '', $href);
        $cleanHref = u($cleanHref)->before('#')->toString();
        if (empty($cleanHref)) {
            return null;
        }
        if (preg_match('{^/(_(profiler|wdt)|css|images|js)/}', $cleanHref)) {
            //                dd($href);
            return null;
        }
        $parts = parse_url($cleanHref);
        if (preg_match('/http/', $cleanHref)) {
            if ($parts['host'] ?? false) {
                return null;
            }
        }

        return $cleanHref;
    }

    private function setRoute(Link $link)
    {
        $path = $link->getPath();
        try {
            // hack
            $path = parse_url($path, PHP_URL_PATH);

            if ($route = $this->router->match($path)) {
                $link->setRoute($route['_route']);
                $controller = $route['_controller'];
                //                $reflection = new \ReflectionMethod($controller);
                foreach (['_route', '_controller'] as $x) {
                    unset($route[$x]);
                }
                if (count($route)) {
                    $link->setRp($route);
                }
            }
        } catch (ResourceNotFoundException $exception) {
            // @todo: check for /public
            $link
                ->setLinkStatus(Link::STATUS_IGNORED);
            return;
        }
        assert($path, "missing path");
    }
}

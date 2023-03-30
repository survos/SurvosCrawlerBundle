<?php

namespace Survos\CrawlerBundle\Services;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CrawlerClient extends KernelBrowser
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(KernelInterface $kernel,TokenStorageInterface $tokenStorage, array $server = [], History $history = null, CookieJar $cookieJar = null)
    {
        parent::__construct($kernel, $server, $history, $cookieJar);
        $this->tokenStorage = $tokenStorage;
    }


    protected function doRequest(object $request): Response
    {
        //        assert(false);
        
        $response = parent::doRequest($request);
        return $response;
    }

        /**
     * @param UserInterface $user
     *
     * @return $this
     */
    public function loginUser(object $user, string $firewallContext = 'main'): static
    {
        if (!interface_exists(UserInterface::class)) {
            throw new \LogicException(sprintf('"%s" requires symfony/security-core to be installed.', __METHOD__));
        }

        if (!$user instanceof UserInterface) {
            throw new \LogicException(sprintf('The first argument of "%s" must be instance of "%s", "%s" provided.', __METHOD__, UserInterface::class, get_debug_type($user)));
        }

        $token = new TestBrowserToken($user->getRoles(), $user, $firewallContext);
        // required for compatibility with Symfony 5.4
        if (method_exists($token, 'isAuthenticated')) {
            $token->setAuthenticated(true, false);
        }

        $container = $this->getContainer();
        $this->tokenStorage->setToken($token);

        if (!$container->has('session.factory')) {
            return $this;
        }

        $session = $container->get('session.factory')->createSession();
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $domains = array_unique(array_map(function (Cookie $cookie) use ($session) {
            return $cookie->getName() === $session->getName() ? $cookie->getDomain() : '';
        }, $this->getCookieJar()->all())) ?: [''];
        foreach ($domains as $domain) {
            $cookie = new Cookie($session->getName(), $session->getId(), null, null, $domain);
            $this->getCookieJar()->set($cookie);
        }

        return $this;
    }
}

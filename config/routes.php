<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Survos\CrawlerBundle\Controller\CrawlerController;
return function (RoutingConfigurator $routes) {
    $routes->add('survos_crawler_results', '/crawler-results')
        ->controller([CrawlerController::class, 'results'])
    ;
};

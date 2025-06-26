<?php

namespace Survos\CrawlerBundle\Controller;

use Survos\CrawlerBundle\Services\CrawlerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CrawlerController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%kernel.environment')] private readonly string $env
    ) {
    }

    #[Route(path: '/crawlerdata', name: 'survos_crawler_data', methods: ['GET'])]
    public function results(CrawlerService $crawlerService): Response
    {
        if ($this->env<>'dev') {
            return new Response("survos_crawler_data is only available in dev");
        }

        // hackish -- get the crawldata of the currently logged in user?
        $filename = $this->projectDir . '/tests/crawldata.json';
            if (!file_exists($filename)) {
                throw $this->createNotFoundException("Run survos:crawl to create $filename");
            }

        $crawlData = json_decode(file_get_contents($filename), true);
        // @todo: filter out null status codes, here or in searchhpanes?
        $tableData = [];
        foreach ($crawlData as $header => $data) {
            foreach ($data as $datum) {
                $datum['user'] = $header;
                $tableData[] = $datum;
            }
        }

        return $this->render('@SurvosCrawler/results.html.twig', [
            'crawlerConfig' => $crawlerService->getConfig(),
            'tableData' => $tableData,
            'crawldata' => $crawlData
        ]);
    }

}

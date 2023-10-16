<?php

namespace Survos\CrawlerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CrawlerController extends AbstractController
{
    public function __construct(private ParameterBagInterface $bag) {

    }
//    #[Route(path: '/crawlerdata', name: 'survos_crawler_data', methods: ['GET'])]
    public function results(): Response
    {
        // hackish -- get the crawldata of the currently logged in user?
        $filename = $this->bag->get('kernel.project_dir') . '/crawldata.json';
        if (!file_exists($filename)) {
            throw new \Exception("Run survos:crawl to create $filename");
        }
        $crawlData = json_decode(file_get_contents($filename), true);
        // @todo: filter out null status codes, here or in searchhpanes?
        foreach ($crawlData as $header => $data) {
            if ($data['statusCode'] ?? false) {
                $filteredData[] = $data;
            }
        }

        return $this->render('@SurvosCrawler/results.html.twig', [
            'crawldata' => $crawlData
        ]);
    }

}

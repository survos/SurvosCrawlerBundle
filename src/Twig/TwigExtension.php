<?php

namespace Survos\CrawlerBundle\Twig;

use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(
    )
    {

    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('link', [$this, 'link'], ['is_safe' => ['html']]),
        ];
    }

    public function link(string $value): string
    {
        // check security and do something if the link isn't valid
        return $value;
    }
}

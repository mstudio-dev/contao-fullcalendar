<?php

namespace ContaoFullcalendar\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use ContaoFullcalendar\ContaoFullcalendarBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(ContaoFullcalendarBundle::class)
                ->setLoadAfter(['ContaoCoreBundle']),
        ];
    }
}

<?php

namespace SwagEssentials;

use Shopware\Components\Plugin;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagEssentials extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $loader = new XmlFileLoader(
            $container,
            new FileLocator()
        );

        $serviceFile = $this->getPath() . '/service.xml';

        if (!file_exists($serviceFile)) {
            throw new \RuntimeException(
                'SwagEssentials: Rename service.xml.dist to service.xml and configure it as needed'
            );
        }

        $loader->load($serviceFile);
    }
}

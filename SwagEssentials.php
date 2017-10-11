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

        /** @var array $swagEssentialsModules */
        $swagEssentialsModules = $container->getParameter('shopware.swag_essentials.modules');

        $loader = new XmlFileLoader(
            $container,
            new FileLocator()
        );

        foreach ($swagEssentialsModules as $module => $active) {
            if (!$active) {
                continue;
            }

            if (strpos($module, 'Redis') === 0) {
                $module = str_replace('Redis', 'Redis/', $module);
                $loader->load($this->getPath() . '/Redis/services.xml');
            }

            $serviceFile = $this->getPath() . '/' . $module . '/services.xml';
            if (file_exists($serviceFile)) {
                $loader->load($serviceFile);
            }
        }
    }
}

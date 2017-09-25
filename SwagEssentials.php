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

        /** @var array $swagEssentialsModules */
        $swagEssentialsModules = $container->getParameter('shopware.swag_essentials.modules');

        foreach ($swagEssentialsModules as $module => $active) {
            $serviceFile = $this->getPath() . '/' . $module . 'services.xml';
            if ($active && file_exists($serviceFile)) {
                $loader->load($serviceFile);
            }
        }
    }
}

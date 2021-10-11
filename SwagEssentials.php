<?php declare(strict_types=1);

namespace SwagEssentials;

use Shopware\Components\Plugin;
use SwagEssentials\Redis\CacheManagerCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class SwagEssentials extends Plugin
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        if (!$container->hasParameter('shopware.swag_essentials.modules')) {
            return;
        }

        /** @var array $swagEssentialsModules */
        $swagEssentialsModules = $container->getParameter('shopware.swag_essentials.modules');

        $loader = new XmlFileLoader($container, new FileLocator());

        $redisLoaded = false;

        foreach ($swagEssentialsModules as $module => $active) {
            if (!$active) {
                continue;
            }

            if (strpos($module, 'Redis') === 0) {
                $module = str_replace('Redis', 'Redis/', $module);
                if (!$redisLoaded) {
                    $loader->load($this->getPath() . '/Redis/services.xml');
                    $redisLoaded = true;
                }
            }

            $serviceFile = $this->getPath() . '/' . $module . '/services.xml';
            if (file_exists($serviceFile)) {
                $loader->load($serviceFile);
            }
        }

        if ($redisLoaded) {
            $container->addCompilerPass(new CacheManagerCompilerPass());
        }
    }
}

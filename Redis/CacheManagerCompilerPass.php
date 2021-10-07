<?php

declare(strict_types=1);

namespace SwagEssentials\Redis;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheManagerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->addCacheManagerArguments($container, 'swag_essentials.redis_product_gateway.cache_manager');
        $this->addCacheManagerArguments($container, 'swag_essentials.redis_plugin_config.cache_manager');
        $this->addCacheManagerArguments($container, 'swag_essentials.redis_translation.cache_manager');
        $this->addCacheManagerArguments($container, 'swag_essentials.redis_store.cache_manager');
    }

    protected function addCacheManagerArguments(ContainerBuilder $container, string $decoratorDefinitionId)
    {
        if (!$container->hasDefinition($decoratorDefinitionId)) {
            return;
        }

        $definition = $container->getDefinition($decoratorDefinitionId);
        $originalDefinition = $container->getDefinition('shopware.cache_manager');

        foreach ($originalDefinition->getArguments() as $argument) {
            $definition->addArgument($argument);
        }
    }
}

<?php

namespace SwagEssentials\CacheMultiplexer\Resource;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Api\Exception\ParameterMissingException;
use Shopware\Components\Api\Resource\Cache as ParentCache;
use Shopware\Components\Api\Exception\NotFoundException;
use Shopware\Components\CacheManager;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Theme\Compiler;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;

class Cache extends ParentCache
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ParentCache
     */
    private $innerCache;

    public function setContainer(Container $container = null)
    {
        parent::setContainer($container);
        $this->innerCache->setContainer($container);
    }

    public function __construct(CacheManager $cacheManager, ParentCache $innerCache)
    {
        $this->cacheManager = $cacheManager;
        $this->innerCache = $innerCache;
    }

    public function getList()
    {
        $data = $this->innerCache->getList()['data'];
        $data[] = $this->getCacheInfo('theme');

        return ['data' => $data, 'total' => count($data)];
    }

    public function getOne($id)
    {
        $this->checkPrivilege('read');

        if (empty($id)) {
            throw new ParameterMissingException('id');
        }

        return $this->getCacheInfo($id);
    }

    protected function clearCache($cache)
    {
        try {
            $this->innerCache->clearCache($cache);
        } catch (NotFoundException $e) {}

        if (($cache === 'all') || (($cache === 'theme'))) {
            $this->cacheManager->clearThemeCache();
            $this->recompileThemeCache();
        }
    }

    private function recompileThemeCache() {
        /** @var Repository $repository */
        $repository = $this->container->get('models')->getRepository(Shop::class);
        /** @var Shop[] $shopsWithThemes */
        $shopsWithThemes = $repository->getShopsWithThemes()->getResult(AbstractQuery::HYDRATE_OBJECT);
        /** @var Compiler $compiler */
        $compiler = $this->container->get('theme_compiler');

        foreach ($shopsWithThemes as $shop) {
            try {
                $compiler->recompile($shop);
            } catch (\Exception $e) {}
        }
    }

    private function getCacheInfo($cache): array
    {
        switch ($cache) {
            case 'http':
                $cacheInfo = $this->cacheManager->getHttpCacheInfo();
                break;
            case 'config':
                $cacheInfo = $this->cacheManager->getConfigCacheInfo();
                break;
            case 'template':
                $cacheInfo = $this->cacheManager->getTemplateCacheInfo();
                break;
            case 'proxy':
                $cacheInfo = $this->cacheManager->getShopwareProxyCacheInfo();
                break;
            case 'doctrine-proxy':
                $cacheInfo = $this->cacheManager->getDoctrineProxyCacheInfo();
                break;
            case 'opcache':
                $cacheInfo = $this->cacheManager->getOpCacheCacheInfo();
                break;
            case 'theme':
                $cacheInfo = $this->cacheManager->getThemeCacheInfo();
                break;
            default:
                throw new NotFoundException(sprintf('Cache "%s" is not a valid cache id.', $cache));
        }

        $cacheInfo['id'] = $cache;

        return $cacheInfo;
    }
}

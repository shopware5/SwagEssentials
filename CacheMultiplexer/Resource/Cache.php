<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\CacheMultiplexer\Resource;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Api\Exception\NotFoundException;
use Shopware\Components\Api\Resource\Cache as ParentCache;
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
    protected $cacheManager;

    /**
     * @var ParentCache
     */
    protected $innerCache;

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
        $data[] = array_merge($this->cacheManager->getThemeCacheInfo(), ['id' => 'theme']);

        return ['data' => $data, 'total' => count($data)];
    }

    public function getOne($id)
    {
        try {
            return $this->innerCache->getOne($id);
        } catch (NotFoundException $e) {
            if ($id === 'theme') {
                return array_merge($this->cacheManager->getThemeCacheInfo(), ['id' => 'theme']);
            }

            throw $e;
        }
    }

    protected function clearCache($cache)
    {
        try {
            $this->innerCache->clearCache($cache);
        } catch (NotFoundException $e) {
            if (($cache === 'all') || (($cache === 'theme'))) {
                $this->cacheManager->clearThemeCache();
                $this->recompileThemeCache();

                return;
            }

            throw $e;
        }
    }

    protected function recompileThemeCache()
    {
        /** @var Repository $repository */
        $repository = $this->container->get('models')->getRepository(Shop::class);
        /** @var Shop[] $shopsWithThemes */
        $shopsWithThemes = $repository->getShopsWithThemes()->getResult(AbstractQuery::HYDRATE_OBJECT);
        /** @var Compiler $compiler */
        $compiler = $this->container->get('theme_compiler');

        foreach ($shopsWithThemes as $shop) {
            $compiler->recompile($shop);
        }
    }
}

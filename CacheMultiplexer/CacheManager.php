<?php declare(strict_types=1);

namespace SwagEssentials\CacheMultiplexer;

use Shopware\Components\DependencyInjection\Container;
use SwagEssentials\Common\CacheManagerDecorationTrait;

/**
 * Class CacheManager replaces the original CacheManager and collects all caches, which have been invalidated
 * during a request. Triggers remote cache invalidation on the end of the request
 */
class CacheManager extends \Shopware\Components\CacheManager
{
    use CacheManagerDecorationTrait;

    private $tags = [];

    /** @var RemoteCacheInvalidator */
    private $cacheInvalidator;

    /**
     * @var \Shopware\Components\CacheManager
     */
    private $innerCacheManager;

    public function __construct(Container $container, \Shopware\Components\CacheManager $innerCacheManager)
    {
        $this->cacheInvalidator = $container->get('swag_essentials.cache_multiplexer.cache_invalidator');
        $this->innerCacheManager = $innerCacheManager;
    }

    public function clearHttpCache()
    {
        $this->tags[] = 'http';

        $this->innerCacheManager->clearHttpCache();
    }

    public function clearTemplateCache()
    {
        $this->tags[] = 'template';

        $this->innerCacheManager->clearTemplateCache();
    }

    public function clearThemeCache()
    {
        $this->tags[] = 'theme';

        $this->innerCacheManager->clearThemeCache();
    }

    public function clearRewriteCache()
    {
        $this->tags[] = 'router';

        $this->innerCacheManager->clearRewriteCache();
    }

    public function clearSearchCache()
    {
        $this->tags[] = 'search';

        $this->innerCacheManager->clearSearchCache();
    }

    public function clearConfigCache()
    {
        $this->tags[] = 'config';

        $this->innerCacheManager->clearConfigCache();
    }

    public function clearProxyCache()
    {
        $this->tags[] = 'proxy';

        $this->innerCacheManager->clearProxyCache();
    }

    public function __destruct()
    {
        // prevent recursive cache invalidation, if cache was invalidated using the API
        if (PHP_SAPI !== 'cli' && strpos(Shopware()->Front()->Request()->getRequestUri(), 'api') !== false) {
            return;
        }

        // if no caches have been invalidated, return
        if (empty($this->tags)) {
            return;
        }

        $this->cacheInvalidator->remoteClear($this->tags);
    }

    public function __call($name, $arguments)
    {
        return $this->innerCacheManager->$name(...$arguments);
    }
}

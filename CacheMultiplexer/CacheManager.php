<?php


namespace SwagEssentials\CacheMultiplexer;

use Shopware\Components\DependencyInjection\Container;

/**
 * Class CacheManager replaces the original CacheManager and collects all caches, which have been invalidated
 * during a request. Triggers remote cache invalidation on the end of the request
 */
class CacheManager extends \Shopware\Components\CacheManager
{
    private $tags = [];
    /** @var RemoteCacheInvalidator  */
    private $cacheInvalidator;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->cacheInvalidator = $container->get("swag_essentials.cache_multiplexer.cache_invalidator");
    }


    public function clearHttpCache()
    {
        $this->tags[] = 'http';

        parent::clearHttpCache();
    }

    public function clearTemplateCache()
    {
        $this->tags[] = 'template';

        parent::clearTemplateCache();
    }

    public function clearThemeCache()
    {
        $this->tags[] = 'theme';

        parent::clearThemeCache();
    }

    public function clearRewriteCache()
    {
        $this->tags[] = 'router';

        parent::clearRewriteCache();
    }

    public function clearSearchCache()
    {
        $this->tags[] = 'search';

        parent::clearSearchCache();
    }

    public function clearConfigCache()
    {
        $this->tags[] = 'config';

        parent::clearConfigCache();
    }

    public function clearProxyCache()
    {
        $this->tags[] = 'proxy';

        parent::clearProxyCache();
    }

    public function clearOpCache()
    {
        parent::clearOpCache();
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

}
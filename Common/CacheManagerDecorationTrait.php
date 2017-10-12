<?php declare(strict_types=1);

namespace SwagEssentials\Common;

/**
 * Trait for decorating the CacheManager service of shopware.
 */
trait CacheManagerDecorationTrait
{
    public function getHttpCacheInfo($request = null)
    {
        return $this->innerCacheManager->getHttpCacheInfo($request = null);
    }

    public function clearHttpCache()
    {
        $this->innerCacheManager->clearHttpCache();
    }

    public function clearTemplateCache()
    {
        $this->innerCacheManager->clearTemplateCache();
    }

    public function clearThemeCache()
    {
        $this->innerCacheManager->clearThemeCache();
    }

    public function clearRewriteCache()
    {
        $this->innerCacheManager->clearRewriteCache();
    }

    public function clearSearchCache()
    {
        $this->innerCacheManager->clearSearchCache();
    }

    public function clearConfigCache()
    {
        $this->innerCacheManager->clearConfigCache();
    }

    public function clearProxyCache()
    {
        $this->innerCacheManager->clearProxyCache();
    }

    public function clearOpCache()
    {
        $this->innerCacheManager->clearOpCache();
    }

    public function getCoreCache()
    {
        return $this->innerCacheManager->getCoreCache();
    }


    public function getConfigCacheInfo()
    {
        return $this->innerCacheManager->getConfigCacheInfo();
    }

    public function getTemplateCacheInfo()
    {
        return $this->innerCacheManager->getTemplateCacheInfo();
    }

    public function getThemeCacheInfo()
    {
        return $this->innerCacheManager->getThemeCacheInfo();
    }

    public function getDoctrineProxyCacheInfo()
    {
        return $this->innerCacheManager->getDoctrineProxyCacheInfo();
    }

    public function getShopwareProxyCacheInfo()
    {
        return $this->innerCacheManager->getShopwareProxyCacheInfo();
    }

    public function getOpCacheCacheInfo()
    {
        return $this->innerCacheManager->getOpCacheCacheInfo();
    }

    public function getDirectoryInfo($dir)
    {
        return $this->innerCacheManager->getDirectoryInfo($dir);
    }

    public function encodeSize($bytes)
    {
        return $this->innerCacheManager->encodeSize($bytes);
    }
}

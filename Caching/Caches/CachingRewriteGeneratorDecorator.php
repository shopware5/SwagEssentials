<?php

declare(strict_types=1);

namespace SwagEssentials\Caching\Caches;

use Shopware\Components\Routing\Context;
use Shopware\Components\Routing\Generators\RewriteGenerator;

class CachingRewriteGeneratorDecorator extends RewriteGenerator
{
    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var RewriteGenerator The previously existing service
     */
    protected $service;

    /**
     * @var int
     */
    protected $ttl;

    public function __construct(\Zend_Cache_Core $cache, RewriteGenerator $service, int $ttl)
    {
        $this->service = $service;
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    protected function getAssembleQuery()
    {
        return $this->service->getAssembleQuery();
    }

    protected function getOrgQueryArray($query)
    {
        return $this->service->getOrgQueryArray($query);
    }

    public function generate(array $params, Context $context)
    {
        $hash = $this->hashItem($params, $context);

        if ($cache = $this->cache->load($hash)) {
            return $cache[0];
        }

        $result = $this->service->generate($params, $context);

        $this->cache->save([$result], $hash, [], $this->ttl);

        return $result;
    }

    public function generateList(array $list, Context $context)
    {
        $multiHash = md5(serialize(['multi_url', $list, $context]));

        // try to get whole list from cache
        if ($cache = $this->cache->load($multiHash)) {
            return array_combine(array_keys($list), $cache);
        }

        $allItems = $this->getCachedItemsFromList($list, $context);

        // list of items which could be resolved from the cache
        $cachedItems = array_filter($allItems);

        // list of items, still not resolved
        $unCachedItems = array_diff_key($list, $cachedItems);

        // request all not resolved items
        $results = $this->service->generateList($unCachedItems, $context);

        // save those new items one by one
        foreach ($results as $result) {
            $this->cache->save([$result], $this->hashItem($result, $context), [], $this->ttl);
        }

        // merge and cache full list
        $totalResult = array_merge($cachedItems, $results);
        $this->cache->save($totalResult, $multiHash, [], $this->ttl);

        return array_combine(array_keys($list), $totalResult);
    }

    protected function getCachedItemsFromList($list, $context)
    {
        return array_map(
            function ($param) use ($context) {
                $hash = $this->hashItem($param, $context);

                return $this->cache->load($hash);
            },
            $list
        );
    }

    /**
     * @param $params
     * @param $context
     *
     * @return string
     */
    protected function hashItem($params, $context)
    {
        return md5(serialize(['single_url', $params, $context]));
    }
}

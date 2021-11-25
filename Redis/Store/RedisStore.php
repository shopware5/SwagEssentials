<?php

declare(strict_types=1);

namespace SwagEssentials\Redis\Store;

use SwagEssentials\Redis\Factory;
use SwagEssentials\Redis\RedisConnection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

/**
 * Based on https://github.com/solilokiam/HttpRedisCache
 * Copyright (c) 2014 Miquel Company Rodriguez
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class RedisStore implements StoreInterface
{
    public const CACHE_KEY = 'sw_http_cache_body';
    public const META_KEY = 'sw_http_cache_meta';
    public const LOCK_KEY = 'sw_http_cache_lock';
    public const ID_KEY = 'sw_http_cache_ids';
    public const CACHE_SIZE_KEY = 'sw_http_cache_size';

    /**
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * @var RedisConnection
     */
    protected $redisClient;

    /**
     * @var array
     */
    protected $cacheCookies;

    /**
     * @var \SplObjectStorage
     */
    protected $keyCache;

    /**
     * @var int zlib compression level used (0 = no compression, 9 = max compression)
     */
    protected $compressionLevel;

    /**
     * @var array
     */
    protected $ignoredUrlParameters;

    public function __construct(array $options)
    {
        $this->redisClient = Factory::factory($options['redisConnections']);
        $this->cacheCookies = $options['cache_cookies'];
        $this->keyPrefix = $options['keyPrefix'] ?? '';
        $this->compressionLevel = $options['compressionLevel'] ?? 9;
        $this->ignoredUrlParameters = $options['ignored_url_parameters'] ?? [];
        $this->keyCache = new \SplObjectStorage();
    }

    /**
     * Check if there is a cached result for a given request. Return null otherwise
     */
    public function lookup(Request $request): ?Response
    {
        $key = $this->getMetadataKey($request);

        $entries = $this->getMetaData($key);
        if ($entries === []) {
            return null;
        }

        // find a cached entry that matches the request.
        $match = null;
        foreach ($entries as $entry) {
            if ($this->requestsMatch(
                $entry[1]['vary'][0] ?? '',
                $request->headers->all(),
                $entry[0]
            )) {
                $match = $entry;

                break;
            }
        }

        if ($match === null) {
            return null;
        }

        [$headers] = array_slice($match, 1, 1);

        $body = $this->redisClient->hGet(
            $this->getBodyKey($headers['x-content-digest'][0]),
            $headers['x-content-digest'][0]
        );

        if (\function_exists('gzuncompress') && $body) {
            $body = gzuncompress($body);
        }

        if ($body) {
            return $this->recreateResponse($headers, $body);
        }

        return null;
    }

    /**
     * Cache a given response for a given request
     */
    public function write(Request $request, Response $response): string
    {
        // write the response body to the entity store if this is the original response
        if (!$response->headers->has('X-Content-Digest')) {
            $digest = $this->generateContentDigestKey($response);

            try {
                $this->save($this->getBodyKey($digest), $digest, (string) $response->getContent());
            } catch (\Throwable $e) {
                throw new \RuntimeException('Unable to store the entity.', 0, $e);
            }

            $response->headers->set('X-Content-Digest', $digest);

            if (!$response->headers->has('Transfer-Encoding')) {
                $response->headers->set('Content-Length', (string) strlen((string) $response->getContent()));
            }
        }

        // read existing cache entries, remove non-varying, and add this one to the list
        $entries = [];
        $vary = $response->headers->get('vary', '');
        $requestHeaders = $this->getRequestHeaders($request);
        $metadataKey = $this->getMetadataKey($request);

        foreach ($this->getMetaData($metadataKey) as $entry) {
            if (!isset($entry[1]['vary'][0])) {
                $entry[1]['vary'] = [''];
            }

            if ($vary !== $entry[1]['vary'][0] || !$this->requestsMatch($vary, $entry[0], $requestHeaders)) {
                $entries[] = $entry;
            }
        }

        $headers = $this->getResponseHeaders($response);

        unset($headers['age']);

        array_unshift($entries, [$requestHeaders, $headers]);

        try {
            $this->save($this->getMetaKey(), $metadataKey, serialize($entries));
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to store the metadata.', 0, $e);
        }

        return $this->storeLookupOptimization($metadataKey, $response);
    }

    /**
     * Invalidates all cache entries that match the request.
     */
    public function invalidate(Request $request): void
    {
        $modified = false;
        $newEntries = [];

        $key = $this->getMetadataKey($request);

        foreach ($this->getMetaData($key) as $entry) {
            //We pass an empty body we only care about headers.
            $response = $this->recreateResponse($entry[1], '');

            if ($response->isFresh()) {
                $response->expire();
                $modified = true;
                $newEntries[] = [$entry[0], $this->getResponseHeaders($response)];
            } else {
                $entries[] = $entry;
            }
        }

        try {
            if ($modified) {
                $this->save($this->getMetaKey(), $key, serialize($newEntries));
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Unable to store the metadata.', 0, $e);
        }
    }

    /**
     * Locks the cache for a given Request.
     *
     * @return bool true if the lock is acquired, the path to the current lock otherwise
     */
    public function lock(Request $request): bool
    {
        $metadataKey = $this->getMetadataKey($request);

        return $this->redisClient->hSetNx($this->getLockKey(), $metadataKey, 1);
    }

    /**
     * Releases the lock for the given Request.
     *
     * @return bool False if the lock file does not exist or cannot be unlocked, true otherwise
     */
    public function unlock(Request $request): bool
    {
        $metadataKey = $this->getMetadataKey($request);

        $result = $this->redisClient->hDel($this->getLockKey(), $metadataKey);

        return $result === 1;
    }

    /**
     * Returns whether or not a lock exists.
     *
     * @return bool true if lock exists, false otherwise
     */
    public function isLocked(Request $request): bool
    {
        $metadataKey = $this->getMetadataKey($request);

        $result = $this->redisClient->hGet($this->getLockKey(), $metadataKey);

        return $result === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($url): bool
    {
        $metadataKey = $this->getMetadataKey(Request::create($url));
        $cacheItem = $this->load($this->getMetaKey(), $metadataKey);
        if (!is_string($cacheItem)) {
            return false;
        }

        // keep track of the overall HTTP cache size
        $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($cacheItem));

        $result = $this->redisClient->hDel($this->getMetaKey(), $metadataKey);

        return $result === 1;
    }

    /**
     * Cleanups locks
     *
     * @return true
     */
    public function cleanup(): bool
    {
        $this->redisClient->del($this->getLockKey());

        return true;
    }

    protected function getShopwareIdKey(string $cacheId): string
    {
        return hash('sha256', $cacheId);
    }

    /**
     * Return the request's headers
     */
    protected function getRequestHeaders(Request $request): array
    {
        return $request->headers->all();
    }

    /**
     * Create content hash
     */
    protected function generateContentDigestKey(Response $response): string
    {
        return md5((string) $response->getContent());
    }

    /**
     * Returns the meta information of a cached item. Will contain e.g. headers and the content hash
     */
    protected function getMetaData(string $key): array
    {
        try {
            $entries = $this->load($this->getMetaKey(), $key);
        } catch (\RuntimeException $e) {
            return [];
        }

        $unserializedEntries = unserialize($entries, ['allowed_classes' => true]);
        if (!is_array($unserializedEntries)) {
            return [];
        }

        return $unserializedEntries;
    }

    /**
     * Store an item to the cache
     */
    protected function save(string $hash, string $key, string $data): void
    {
        // keep track of the overall HTTP cache size
        $this->redisClient->incrBy($this->getCacheSizeKey(), strlen($data));

        if (\function_exists('gzcompress')) {
            $data = gzcompress($data, $this->compressionLevel);
        }

        $this->redisClient->hSet($hash, $key, $data);
    }

    /**
     * Load a cached item
     */
    protected function load(string $hash, string $key): string
    {
        $return = $this->redisClient->hGet($hash, $key);
        if (\function_exists('gzuncompress') && is_string($return)) {
            $return = @gzuncompress($return);
        }

        if ($return === false) {
            throw new \RuntimeException('Could not uncompress string');
        }

        return $return;
    }

    /**
     * Determines whether two Request HTTP header sets are non-varying based on
     * the "vary" response header value provided
     *
     * @param string $vary A Response vary header
     * @param array  $env1 A Request HTTP header array
     * @param array  $env2 A Request HTTP header array
     *
     * @return bool true if the two environments match, false otherwise
     */
    protected function requestsMatch(string $vary, array $env1, array $env2): bool
    {
        if (empty($vary)) {
            return true;
        }

        $splitVary = preg_split('/[\s,]+/', $vary);
        if ($splitVary === false) {
            return false;
        }

        foreach ($splitVary as $header) {
            $key = str_replace('_', '-', strtolower($header));
            $v1 = $env1[$key] ?? null;
            $v2 = $env2[$key] ?? null;
            if ($v1 !== $v2) {
                return false;
            }
        }

        return true;
    }

    /**
     * Persists the Response HTTP headers.
     *
     * @return array An array of HTTP headers
     */
    protected function getResponseHeaders(Response $response): array
    {
        $headers = $response->headers->all();
        $headers['X-Status'] = [$response->getStatusCode()];

        return $headers;
    }

    /**
     * Create a response object with the desired headers and body
     */
    protected function recreateResponse(array $headers, string $body): Response
    {
        $status = $headers['X-Status'][0];
        unset($headers['X-Status']);

        return new Response($body, $status, $headers);
    }

    /**
     * Clears the cache completely
     */
    public function purgeAll(): void
    {
        foreach (array_merge(range('a', 'z'), range(0, 9)) as $bodyKey) {
            $this->redisClient->del($this->getBodyKey((string) $bodyKey));
        }
        $this->redisClient->del($this->getMetaKey());
        $this->redisClient->del($this->getIdKey());
        $this->redisClient->del($this->getLockKey());

        // keep track of the overall HTTP cache size
        $this->redisClient->set($this->getCacheSizeKey(), 0, ['ex']);
    }

    /**
     * Clears all cached pages with certain headers in them
     */
    public function purgeByHeader(string $name, ?string $value = null): bool
    {
        if ($name === 'x-shopware-cache-id') {
            return $this->purgeByShopwareId($value);
        }

        throw new \RuntimeException('RedisStore does not support purging by headers other than `x-shopware-cache-id`');
    }

    /**
     * Clear all cached pages with a certain shopwareID in them
     */
    protected function purgeByShopwareId(?string $id): bool
    {
        if ($id === null) {
            return false;
        }

        $cacheInvalidateKey = $this->getShopwareIdKey($id);
        $cacheItem = $this->load($this->getIdKey(), $cacheInvalidateKey);
        if (!is_string($cacheItem)) {
            return false;
        }

        $content = json_decode($cacheItem, true);
        if (!is_array($content)) {
            return false;
        }

        // unlink all cache files which contain the given id
        foreach ($content as $cacheKey => $headerKey) {
            // keep track of the overall HTTP cache size
            $bodyKey = $this->load($this->getBodyKey($cacheKey), $cacheKey);
            if (is_string($bodyKey)) {
                $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($bodyKey));
                $this->redisClient->hDel($this->getBodyKey($cacheKey), $cacheKey);
            }

            $metaKey = $this->load($this->getMetaKey(), $headerKey);
            if (is_string($metaKey)) {
                $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($metaKey));
                $this->redisClient->hDel($this->getMetaKey(), $headerKey);
            }
        }

        $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($cacheItem));
        $this->redisClient->hDel($this->getIdKey(), $cacheInvalidateKey);

        return true;
    }

    /**
     * Get the affected shopwareIDs from the current response and store them in a separate key,
     * so that we are able to easily invalidate all pages with a certain ID
     */
    protected function storeLookupOptimization(string $metadataKey, Response $response): string
    {
        if (!$response->headers->has('x-shopware-cache-id') || !$response->headers->has('x-content-digest')) {
            return $metadataKey;
        }

        $cacheIds = array_filter(explode(';', $response->headers->get('x-shopware-cache-id', '')));
        $cacheKey = $response->headers->get('x-content-digest');

        foreach ($cacheIds as $cacheId) {
            $key = $this->getShopwareIdKey($cacheId);

            try {
                $cacheItem = $this->load($this->getIdKey(), $key);
                $content = json_decode($cacheItem, true);
                if (!is_array($content)) {
                    $content = [];
                }
            } catch (\RuntimeException $e) {
                $content = [];
            }

            // Storing the headerKey and the cacheKey will increase the lookup file size a bit
            // but save a lot of reads when invalidating
            $content[$cacheKey] = $metadataKey;

            try {
                $this->save($this->getIdKey(), $key, json_encode($content));
            } catch (\Throwable $e) {
                throw new \RuntimeException(sprintf('Could not write cacheKey %s', $key), 0, $e);
            }
        }

        return $metadataKey;
    }

    /**
     * Build metadata key from URL + some cookies
     */
    protected function getMetadataKey(Request $request): string
    {
        if (isset($this->keyCache[$request])) {
            return $this->keyCache[$request];
        }

        $uri = $this->verifyIgnoredParameters($request);

        foreach ($this->cacheCookies as $cookieName) {
            if ($request->cookies->has($cookieName)) {
                $uri .= '&__' . $cookieName . '=' . $request->cookies->get($cookieName);
            }
        }

        $this->keyCache[$request] = hash('sha256', $uri);

        return $this->keyCache[$request];
    }

    /**
     * Sort query params, so that shop.de/?foo=1&bar=2 and shop.de/?bar=2&foo=1 are handled as the same cached page
     */
    protected function sortQueryStringParameters(array $params): string
    {
        $sParams = urldecode(http_build_query($params));
        $query = explode('&', $sParams);

        usort(
            $query,
            function ($val1, $val2) {
                return strcmp($val1, $val2);
            }
        );

        return http_build_query($query);
    }

    /**
     * Return information regarding cache keys and size
     */
    public function getCacheInfo(): array
    {
        $entries = array_sum(
            [
                $this->redisClient->hLen($this->getBodyKey('*')),
                $this->redisClient->hLen($this->getMetaKey()),
                $this->redisClient->hLen($this->getIdKey()),
            ]
        );

        $size = $this->redisClient->get($this->getCacheSizeKey());
        $memory = (array) $this->redisClient->info('memory');
        $freeSpace = $memory['total_system_memory_human'] ?? '';

        return compact('entries', 'size', 'freeSpace');
    }

    /**
     * Get the prefixed key for body entries
     */
    protected function getBodyKey(string $key): string
    {
        return $this->getKeyPrefix() . self::CACHE_KEY . '-' . $key[0];
    }

    /**
     * Get the prefixed key for meta entries
     */
    protected function getMetaKey(): string
    {
        return $this->getKeyPrefix() . self::META_KEY;
    }

    /**
     * Get the prefixed key for size entries
     */
    protected function getCacheSizeKey(): string
    {
        return $this->getKeyPrefix() . self::CACHE_SIZE_KEY;
    }

    /**
     * Get the prefixed key for id entries
     */
    protected function getIdKey(): string
    {
        return $this->getKeyPrefix() . self::ID_KEY;
    }

    /**
     * Get the prefixed key for lock entries
     */
    protected function getLockKey(): string
    {
        return $this->getKeyPrefix() . self::LOCK_KEY;
    }

    /**
     * get the key prefix
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * set the key prefix
     *
     * @return RedisStore
     */
    public function setKeyPrefix(string $keyPrefix): self
    {
        $this->keyPrefix = $keyPrefix;

        return $this;
    }

    protected function verifyIgnoredParameters(Request $request): string
    {
        $requestParams = $request->query->all();

        if (count($requestParams) === 0) {
            return $request->getUri();
        }

        $parsedQuery = (string) parse_url($request->getUri(), PHP_URL_QUERY);
        $query = [];

        parse_str($parsedQuery, $query);

        $params = array_diff_key(
            $query,
            array_flip($this->ignoredUrlParameters)
        );

        //Sort query parameters
        $stringParams = $this->sortQueryStringParameters($params);

        $path = $request->getPathInfo();

        // Normalize URL to consistently return the same path even when variables are present
        return sprintf(
            '%s%s%s',
            $request->getSchemeAndHttpHost(),
            $path,
            empty($stringParams) ? '' : "?$stringParams"
        );
    }
}

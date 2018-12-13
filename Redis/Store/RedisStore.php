<?php

namespace SwagEssentials\Redis\Store;

use Shopware\Kernel;
use SwagEssentials\Redis\Factory;
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
    const CACHE_KEY = 'sw_http_cache_body';
    const META_KEY = 'sw_http_cache_meta';
    const LOCK_KEY = 'sw_http_cache_lock';
    const ID_KEY = 'sw_http_cache_ids';
    const CACHE_SIZE_KEY = 'sw_http_cache_size';

    /**
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * @var \Redis
     */
    protected $redisClient;

    /** @var array */
    protected $cacheCookies;

    /**
     * @var \SplObjectStorage
     */
    protected $keyCache;

    protected $ignoredUrlParameters;

    public function __construct($options, Kernel $kernel)
    {
        $this->cacheCookies = $options['cache_cookies'];
        $this->keyPrefix = $options['keyPrefix'] ?? '';

        $this->redisClient = Factory::factory($options['redisConnections']);

        $this->ignoredUrlParameters = $options['ignored_url_parameters'] ?? [];

        $this->keyCache = new \SplObjectStorage();
    }

    /**
     * Check if there is a cached result for a given request. Return null otherwise
     *
     * @param Request $request
     * @return null|Response
     */
    public function lookup(Request $request)
    {
        $key = $this->getMetadataKey($request);

        if (!$entries = $this->getMetadata($key)) {
            return null;
        }

        // find a cached entry that matches the request.
        $match = null;
        foreach ($entries as $entry) {
            if ($this->requestsMatch(
                $entry[1]['vary'][0] ?? '',
                $request->headers->all(),
                $entry[0]
            )
            ) {
                $match = $entry;
                break;
            }
        }

        if (null === $match) {
            return null;
        }

        list($headers) = array_slice($match, 1, 1);


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
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function write(Request $request, Response $response): string
    {
        // write the response body to the entity store if this is the original response
        if (!$response->headers->has('X-Content-Digest')) {
            $digest = $this->generateContentDigestKey($response);

            if ($this->save($this->getBodyKey($digest), $digest, $response->getContent()) === false) {
                throw new \RuntimeException('Unable to store the entity.');
            }

            $response->headers->set('X-Content-Digest', $digest);

            if (!$response->headers->has('Transfer-Encoding')) {
                $response->headers->set('Content-Length', strlen($response->getContent()));
            }
        }

        // read existing cache entries, remove non-varying, and add this one to the list
        $entries = [];
        $vary = $response->headers->get('vary');
        $requestHeaders = $this->getRequestHeaders($request);
        $metadataKey = $this->getMetadataKey($request);

        foreach ($this->getMetadata($metadataKey) as $entry) {
            if (!isset($entry[1]['vary'][0])) {
                $entry[1]['vary'] = [''];
            }

            if ($vary != $entry[1]['vary'][0] || !$this->requestsMatch($vary, $entry[0], $requestHeaders)) {
                $entries[] = $entry;
            }
        }

        $headers = $this->getResponseHeaders($response);

        unset($headers['age']);

        array_unshift($entries, [$requestHeaders, $headers]);

        if (false === $this->save($this->getMetaKey(), $metadataKey, serialize($entries))) {
            throw new \RuntimeException('Unable to store the metadata.');
        }

        return $this->storeLookupOptimization($metadataKey, $response);
    }

    /**
     * Invalidates all cache entries that match the request.
     *
     * @param Request $request A Request instance
     */
    public function invalidate(Request $request)
    {
        $modified = false;
        $newEntries = [];

        $key = $this->getMetadataKey($request);

        foreach ($this->getMetadata($key) as $entry) {
            //We pass an empty body we only care about headers.
            $response = $this->recreateResponse($entry[1], null);

            if ($response->isFresh()) {
                $response->expire();
                $modified = true;
                $newEntries[] = [$entry[0], $this->getResponseHeaders($response)];
            } else {
                $entries[] = $entry;
            }
        }

        if ($modified) {
            if ($this->save($this->getMetaKey(), $key, serialize($newEntries)) === false) {
                throw new \RuntimeException('Unable to store the metadata.');
            }
        }
    }

    /**
     * Locks the cache for a given Request.
     *
     * @param Request $request A Request instance
     * @return Boolean|string true if the lock is acquired, the path to the current lock otherwise
     */
    public function lock(Request $request): bool
    {
        $metadataKey = $this->getMetadataKey($request);

        return $this->redisClient->hSetNx($this->getLockKey(), $metadataKey, 1);
    }

    /**
     * Releases the lock for the given Request.
     *
     * @param Request $request A Request instance
     * @return Boolean False if the lock file does not exist or cannot be unlocked, true otherwise
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
     * @param Request $request A Request instance
     * @return Boolean true if lock exists, false otherwise
     */
    public function isLocked(Request $request): bool
    {
        $metadataKey = $this->getMetadataKey($request);

        $result = $this->redisClient->hGet($this->getLockKey(), $metadataKey);

        return $result === 1;
    }

    /**
     * Purges data for the given URL.
     *
     * @param string $url A URL
     * @return Boolean true if the URL exists and has been purged, false otherwise
     */
    public function purge($url): bool
    {
        $metadataKey = $this->getMetadataKey(Request::create($url));

        // keep track of the overall HTTP cache size
        $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($this->load($this->getMetaKey(), $metadataKey)));

        $result = $this->redisClient->hDel($this->getMetaKey(), $metadataKey);

        return $result === 1;
    }

    /**
     * Cleanups locks
     */
    public function cleanup(): bool
    {
        $this->redisClient->del($this->getLockKey());

        return true;
    }

    private function getShopwareIdKey($cacheId)
    {
        // return hash('sha256', $cacheId);

        return $cacheId;
    }

    /**
     * Return the request's headers
     *
     * @param Request $request
     * @return array
     */
    private function getRequestHeaders(Request $request): array
    {
        return $request->headers->all();
    }

    /**
     * Create content hash
     *
     * @param Response $response
     * @return string
     */
    protected function generateContentDigestKey(Response $response): string
    {
        return md5($response->getContent());
    }

    /**
     * Returns the meta information of a cached item. Will contain e.g. headers and the content hash
     *
     * @param $key
     * @return array
     */
    private function getMetadata($key): array
    {
        if (false === $entries = $this->load($this->getMetaKey(), $key)) {
            return [];
        }

        return unserialize($entries, ['allowed_classes' => true]);
    }

    /**
     * Store an item to the cache
     *
     * @param $hash
     * @param $key
     * @param $data
     */
    private function save($hash, $key, $data)
    {
        // keep track of the overall HTTP cache size
        $this->redisClient->incrBy($this->getCacheSizeKey(), strlen($data));

        if (\function_exists('gzcompress')) {
            $data = gzcompress($data, 9);
        }

        $this->redisClient->hSet($hash, $key, $data);
    }

    /**
     * Load an cached item
     *
     * @param $hash
     * @param $key
     * @return string|bool
     */
    private function load($hash, $key)
    {
        $return = $this->redisClient->hGet($hash, $key);
        if (\function_exists('gzuncompress') && $return) {
            $return = gzuncompress($return);
        }

        return $return;
    }

    /**
     * Determines whether two Request HTTP header sets are non-varying based on
     * the vary response header value provided.
     *
     * @param string $vary A Response vary header
     * @param array $env1 A Request HTTP header array
     * @param array $env2 A Request HTTP header array
     * @return bool true if the two environments match, false otherwise
     */
    private function requestsMatch($vary, $env1, $env2): bool
    {
        if (empty($vary)) {
            return true;
        }

        foreach (preg_split('/[\s,]+/', $vary) as $header) {
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
     * @param Response $response A Response instance
     * @return array An array of HTTP headers
     */
    private function getResponseHeaders(Response $response): array
    {
        $headers = $response->headers->all();
        $headers['X-Status'] = [$response->getStatusCode()];

        return $headers;
    }

    /**
     * Create a response object with the desired headers and body
     *
     * @param $headers
     * @param $body
     * @return Response
     */
    private function recreateResponse($headers, $body): Response
    {
        $status = $headers['X-Status'][0];
        unset($headers['X-Status']);

        return new Response($body, $status, $headers);
    }

    /**
     * Clears the cache completely
     */
    public function purgeAll()
    {
        foreach (array_merge(range('a', 'z'), range(0, 9)) as $bodyKey) {
            $this->redisClient->del($this->getBodyKey((string) $bodyKey));
        }
        $this->redisClient->del($this->getMetaKey());
        $this->redisClient->del($this->getIdKey());
        $this->redisClient->del($this->getLockKey());

        // keep track of the overall HTTP cache size
        $this->redisClient->set($this->getCacheSizeKey(), 0);
    }

    /**
     * Clears all cached pages with certain headers in them
     *
     * @param $name
     * @param null $value
     * @return bool
     */
    public function purgeByHeader($name, $value = null): bool
    {
        if ($name === 'x-shopware-cache-id') {
            return $this->purgeByShopwareId($value);
        }

        throw new \RuntimeException('RedisStore does not support purging by headers other than `x-shopware-cache-id`');
    }

    /**
     * Clear all cached pages with a certain shopwareID in them
     *
     * @param $id
     * @return bool
     */
    private function purgeByShopwareId($id): bool
    {
        if (!$id) {
            return false;
        }

        $cacheInvalidateKey = $this->getShopwareIdKey($id);

        if (!$content = json_decode($this->load($this->getIdKey(), $cacheInvalidateKey), true)) {
            return false;
        }

        // unlink all cache files which contain the given id
        foreach ($content as $cacheKey => $headerKey) {
            // keep track of the overall HTTP cache size
            $this->redisClient->decrBy(
                $this->getCacheSizeKey(),
                strlen($this->load($this->getBodyKey($cacheKey), $cacheKey))
            );
            $this->redisClient->decrBy($this->getCacheSizeKey(), strlen($this->load($this->getMetaKey(), $headerKey)));
            $this->redisClient->decrBy(
                $this->getCacheSizeKey(),
                strlen($this->load($this->getIdKey(), $cacheInvalidateKey))
            );

            // remove fields
            $this->redisClient->hDel($this->getBodyKey($cacheKey), $cacheKey);
            $this->redisClient->hDel($this->getMetaKey(), $headerKey);
            $this->redisClient->hDel($this->getIdKey(), $cacheInvalidateKey);
        }

        return true;
    }

    /**
     * Get the affected shopwareIDs from the current response and store them in a separate key, so that we are able
     * to easily invalidate all pages with a certain ID
     *
     * @param $metadataKey
     * @param Response $response
     * @return mixed
     */
    private function storeLookupOptimization($metadataKey, Response $response)
    {
        if (!$response->headers->has('x-shopware-cache-id') || !$response->headers->has('x-content-digest')) {
            return $metadataKey;
        }

        $cacheIds = array_filter(explode(';', $response->headers->get('x-shopware-cache-id')));
        $cacheKey = $response->headers->get('x-content-digest');

        foreach ($cacheIds as $cacheId) {
            $key = $this->getShopwareIdKey($cacheId);
            if (!$content = json_decode($this->load($this->getIdKey(), $key), true)) {
                $content = [];
            }

            // Storing the headerKey and the cacheKey will increase the lookup file size a bit
            // but save a lot of reads when invalidating
            $content[$cacheKey] = $metadataKey;

            if ($this->save($this->getIdKey(), $key, json_encode($content)) === !false) {
                throw new \RuntimeException("Could not write cacheKey $key");
            }
        }

        return $metadataKey;
    }

    /**
     * Build metadata key from URL + some cookies
     *
     * @param Request $request
     * @return mixed
     */
    protected function getMetadataKey(Request $request)
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
     *
     * @param $url
     * @return string
     */
    private function sortQueryStringParameters($params): string
    {
        $sParams = urldecode(http_build_query($params));
        $query = explode('&', $sParams);

        usort(
            $query, function ($val1, $val2) {
            return strcmp($val1, $val2);
        }
        );

        return implode('&', $query);
    }

    /**
     * Return information regarding cache keys and size
     *
     * @return array
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

        return compact($entries, $size);
    }

    /**
     * Get the prefixed key for body entries
     *
     * @return string
     */
    protected function getBodyKey(string $key): string
    {
        return $this->getKeyPrefix() . self::CACHE_KEY . '-' . $key[0];
    }

    /**
     * Get the prefixed key for meta entries
     *
     * @return string
     */
    protected function getMetaKey(): string
    {
        return $this->getKeyPrefix() . self::META_KEY;
    }

    /**
     * Get the prefixed key for size entries
     *
     * @return string
     */
    protected function getCacheSizeKey(): string
    {
        return $this->getKeyPrefix() . self::CACHE_SIZE_KEY;
    }

    /**
     * Get the prefixed key for id entries
     *
     * @return string
     */
    protected function getIdKey(): string
    {
        return $this->getKeyPrefix() . self::ID_KEY;
    }

    /**
     * Get the prefixed key for lock entries
     *
     * @return string
     */
    protected function getLockKey(): string
    {
        return $this->getKeyPrefix() . self::LOCK_KEY;
    }

    /**
     * get the key prefix
     *
     * @return string
     */
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * set the key prefix
     *
     * @param string $keyPrefix
     * @return RedisStore
     */
    public function setKeyPrefix(string $keyPrefix): self
    {
        $this->keyPrefix = $keyPrefix;

        return $this;
    }

    private function verifyIgnoredParameters(Request $request)
    {
        $requestParams = $request->query->all();

        if (count($requestParams) === 0) {
            return $request->getUri();
        }


        $parsed = parse_url($request->getUri());
        $query = [];

        parse_str($parsed['query'], $query);

        $params = array_diff_key(
            $query,
            array_flip($this->ignoredUrlParameters)
        );

        /**
         * Sort query parameters
         */
        $stringParams = $this->sortQueryStringParameters($params);

        $path = $request->getPathInfo();

        /**
         * Normalize URL to consistently return the same path even when variables are present
         */
        $uri = sprintf(
            '%s%s%s',
            $request->getSchemeAndHttpHost(),
            $path,
            empty($stringParams) ? '' : "?$stringParams"
        );

        return $uri;
    }
}

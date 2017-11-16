<?php

namespace SwagEssentials\Redis\Translation;

use Doctrine\DBAL\Connection;

class Translation extends \Shopware_Components_Translation
{
    const HASH_NAME = \Shopware::VERSION . '-sw_translation';

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var int
     */
    private $cachingTtlTranslation;

    /**
     * @param Connection|null $connection
     * @param \Redis $redis
     * @param int $cachingTtlTranslation
     */
    public function __construct(Connection $connection = null, \Redis $redis, int $cachingTtlTranslation)
    {
        parent::__construct($connection);
        $this->redis = $redis;
        $this->cachingTtlTranslation = $cachingTtlTranslation;
    }

    /**
     * {@inheritdoc}
     */
    public function filterData($type, array $data, $key = null)
    {
        $parameters = [
            'type' => $type,
            'data' => implode('|', $data),
            'key' => $key,
        ];

        $hashKey = implode('|', $parameters);

        $result = $this->redis->hGet(self::HASH_NAME, $hashKey);

        if ($result) {
            return json_decode($result, true);
        }

        $result = parent::filterData($type, $data, $key);

        $this->redis->hSet(self::HASH_NAME, $hashKey, json_encode($result));
        $this->redis->expire(self::HASH_NAME, $this->cachingTtlTranslation);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function read($language, $type, $key = 1, $merge = false)
    {
        $parameters = [
            'language' => $language,
            'type' => $type,
            'key' => $key,
            'merge' => $merge,
        ];

        $hashKey = implode('|', $parameters);

        $result = $this->redis->hGet(self::HASH_NAME, $hashKey);

        if ($result) {
            return json_decode($result, true);
        }

        $result = parent::read($language, $type, $key, $merge);
        $this->redis->hSet(self::HASH_NAME, $hashKey, json_encode($result));
        $this->redis->expire(self::HASH_NAME, $this->cachingTtlTranslation);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readBatch($language, $type, $key = 1, $merge = false)
    {
        $parameters = [
            'method' => 'readBatch',
            'language' => $language,
            'type' => $type,
            'key' => $key,
            'merge' => $merge,
        ];

        $hashKey = implode('|', $parameters);

        $result = $this->redis->hGet(self::HASH_NAME, $hashKey);

        if ($result) {
            return json_decode($result, true);
        }

        $result = parent::readBatch($language, $type, $key, $merge);
        $this->redis->hSet(self::HASH_NAME, $hashKey, json_encode($result));
        $this->redis->expire(self::HASH_NAME, $this->cachingTtlTranslation);

        return $result;
    }
}

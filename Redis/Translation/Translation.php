<?php

namespace SwagEssentials\Redis\Translation;

use Doctrine\DBAL\Connection;
use SwagEssentials\Redis\RedisConnection;

class Translation extends \Shopware_Components_Translation
{
    const HASH_NAME = 'sw_translation';

    /**
     * @var RedisConnection
     */
    private $redis;

    /**
     * @var int
     */
    private $cachingTtlTranslation;

    /**
     * @var \Enlight_Controller_Front
     */
    private $front;

    /**
     * @param Connection $connection
     * @param RedisConnection $redis
     * @param $auth
     * @param int $cachingTtlTranslation
     */
    public function __construct(
        Connection $connection,
        RedisConnection $redis,
        int $cachingTtlTranslation
    ) {
        parent::__construct($connection);
        $this->redis = $redis;
        $this->cachingTtlTranslation = $cachingTtlTranslation;
        $this->front = Shopware()->Container()->get('Front');
    }

    /**
     * {@inheritdoc}
     */
    public function filterData($type, array $data, $key = null)
    {
        if ($this->isNotFrontend()) {
            return parent::filterData($type, $data, $key);
        }

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
        if ($this->isNotFrontend()) {
            return parent::read($language, $type, $key, $merge);
        }

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
        if ($this->isNotFrontend()) {
            return parent::readBatch($language, $type, $key, $merge);
        }

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

    private function isNotFrontend(): bool
    {
        if (!$this->front->Request()) {
            return false;
        }

        return $this->front->Request()->getModuleName() !== 'frontend';
    }
}

<?php

declare(strict_types=1);

namespace SwagEssentials\Redis\Translation;

use Doctrine\DBAL\Connection;
use SwagEssentials\Redis\RedisConnection;

class Translation extends \Shopware_Components_Translation
{
    public const HASH_NAME = 'sw_translation';

    /**
     * @var RedisConnection
     */
    protected $redis;

    /**
     * @var int
     */
    protected $cachingTtlTranslation;

    /**
     * @var \Enlight_Controller_Front
     */
    protected $front;

    public function __construct(
        Connection $connection,
        RedisConnection $redis,
        int $cachingTtlTranslation,
        \Enlight_Controller_Front $front
    ) {
        parent::__construct($connection, Shopware()->Container());
        $this->redis = $redis;
        $this->cachingTtlTranslation = $cachingTtlTranslation;
        $this->front = $front;
    }

    /**
     * {@inheritdoc}
     */
    public function filterData($type, array $data, $key = null)
    {
        if ($this->isNotFrontend()) {
            return parent::filterData($type, $data, $key);
        }

        $hashKey = json_encode(func_get_args());

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

        $hashKey = json_encode(func_get_args());

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

        $hashKey = json_encode(func_get_args());

        $result = $this->redis->hGet(self::HASH_NAME, $hashKey);

        if ($result) {
            return json_decode($result, true);
        }

        $result = parent::readBatch($language, $type, $key, $merge);
        $this->redis->hSet(self::HASH_NAME, $hashKey, json_encode($result));
        $this->redis->expire(self::HASH_NAME, $this->cachingTtlTranslation);

        return $result;
    }

    protected function isNotFrontend(): bool
    {
        if (!$this->front->Request()) {
            return false;
        }

        return $this->front->Request()->getModuleName() !== 'frontend';
    }
}

<?php

namespace SwagEssentials\RedisNumberRange;

use Shopware\Components\NumberRangeIncrementerInterface;

class RedisNumberRangeIncrementer implements NumberRangeIncrementerInterface
{
    const HASH_NAME = 'sw_number_range';

    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function increment($name)
    {
        return $this->redis->hIncrBy(self::HASH_NAME, $name, 1);
    }

}
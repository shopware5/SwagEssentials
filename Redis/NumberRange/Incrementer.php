<?php declare(strict_types=1);

namespace SwagEssentials\Redis\NumberRange;

use Shopware\Components\NumberRangeIncrementerInterface;
use SwagEssentials\Redis\RedisConnection;

class Incrementer implements NumberRangeIncrementerInterface
{
    const HASH_NAME = 'sw_number_range';

    /**
     * @var RedisConnection
     */
    private $redis;

    public function __construct(RedisConnection $redis)
    {
        $this->redis = $redis;
    }

    public function increment($name): int
    {
        return $this->redis->hIncrBy(self::HASH_NAME, $name, 1);
    }
}

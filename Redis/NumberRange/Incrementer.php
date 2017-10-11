<?php declare(strict_types=1);

namespace SwagEssentials\Redis\NumberRange;

use Shopware\Components\NumberRangeIncrementerInterface;

class Incrementer implements NumberRangeIncrementerInterface
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

    public function increment($name): int
    {
        return $this->redis->hIncrBy(self::HASH_NAME, $name, 1);
    }
}

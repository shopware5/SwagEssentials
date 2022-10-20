<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Redis\NumberRange;

use Shopware\Components\NumberRangeIncrementerInterface;
use SwagEssentials\Redis\RedisConnection;

class Incrementer implements NumberRangeIncrementerInterface
{
    public const HASH_NAME = 'sw_number_range';

    /**
     * @var RedisConnection
     */
    protected $redis;

    public function __construct(RedisConnection $redis)
    {
        $this->redis = $redis;
    }

    public function increment($name): int
    {
        return $this->redis->hIncrBy(self::HASH_NAME, $name, 1);
    }
}

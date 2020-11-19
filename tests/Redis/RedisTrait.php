<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Redis;

trait RedisTrait
{
    /**
     * @before
     */
    public function activateRedis(): void
    {
    }

    /**
     * @after
     */
    public function deactivateRedis(): void
    {
    }
}

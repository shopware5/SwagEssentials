<?php

declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

class KernelStorage
{
    protected static $kernel;

    /**
     * @var TestFactoryInterface
     */
    public static $kernelFactory;

    public static function init(TestFactoryInterface $kernelFactory)
    {
        self::$kernelFactory = $kernelFactory;
    }

    public static function store(TestKernelInterface $kernel)
    {
        self::$kernel = $kernel;
    }

    public static function retrieve(): TestKernelInterface
    {
        return self::$kernel;
    }

    public static function unset()
    {
        self::$kernel = null;
    }

    public static function has(): bool
    {
        return self::$kernel !== null;
    }
}

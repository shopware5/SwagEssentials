<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use Symfony\Component\HttpKernel\Client;

interface TestFactoryInterface
{
    public function bootKernel(): TestKernelInterface;

    public function createTestClient(TestKernelInterface $kernel): Client;
}

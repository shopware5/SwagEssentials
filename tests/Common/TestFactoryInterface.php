<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Symfony\Component\HttpKernel\Client;

interface TestFactoryInterface
{
    public function bootKernel(): TestKernelInterface;

    public function createTestClient(TestKernelInterface $kernel): Client;
}

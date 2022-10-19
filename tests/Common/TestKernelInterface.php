<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

interface TestKernelInterface extends HttpKernelInterface
{
    /**
     * @return ContainerInterface
     */
    public function getContainer();

    public function beforeTest();

    public function beforeUnset();

    public function beforeWebTest();

    public function afterWebTest();

    public function authenticateApiUser();
}

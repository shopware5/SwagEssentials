<?php declare(strict_types=1);

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

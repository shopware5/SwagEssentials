<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists(__DIR__ . '/../shopware/autoload.php')) {
    require_once __DIR__ . '/../shopware/autoload.php';
}
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/test_constants.php';

use SwagEssentials\Tests\Common\KernelStorage;
use SwagEssentials\Tests\Common\TestFactory;

KernelStorage::init(new TestFactory());

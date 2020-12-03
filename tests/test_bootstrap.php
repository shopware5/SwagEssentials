<?php declare(strict_types=1);

require_once __DIR__ . '/../shopware/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/test_constants.php';

use SwagEssentials\Tests\Common\KernelStorage;
use SwagEssentials\Tests\Common\TestFactory;

KernelStorage::init(new TestFactory($loader));

<?php

declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use DomainException;

class EmptyShopwareApplication
{
    public function __isset($name)
    {
        $this->throwException('isset', $name);
    }

    public function __set($name, $value)
    {
        $this->throwException('set', $name);
    }

    public function __get($name)
    {
        $this->throwException('get', $name);
    }

    public function __call($name, $arguments)
    {
        $this->throwException('call', $name);
    }

    protected function throwException(string $type, string $name)
    {
        throw new DomainException('Restricted to ' . $type . ' ' . $name . ' on Shopware() , because you should not have a kernel in this test case.');
    }
}

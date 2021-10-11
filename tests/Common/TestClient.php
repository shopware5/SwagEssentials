<?php

declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use Symfony\Component\HttpKernel\Client;

class TestClient extends Client
{
    public function request($method, $uri, array $parameters = [], array $files = [], array $server = [], $content = null, $changeHistory = true)
    {
        $this->kernel->getContainer()->get('front')->setResponse('Enlight_Controller_Response_ResponseTestCase');

        return parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }
}

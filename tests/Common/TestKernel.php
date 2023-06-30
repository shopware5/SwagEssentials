<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Shopware\Kernel;
use Shopware\Models\Shop\Shop;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class TestKernel extends Kernel implements TerminableInterface, TestKernelInterface
{
    public function handle(SymfonyRequest $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        if ($this->booted === false) {
            $this->boot();
        }

        $container = $this->getContainer();

        if ($container->has('shop')) {
            $container->get('shop')->setHost($request->getHost());
        }

        /** @var \Enlight_Controller_Front $front */
        $front = $this->container->get('front');

        $front->Response()->setHttpResponseCode(200);

        $request = $this->transformSymfonyRequestToEnlightRequest($request);
        $front->setRequest($request);
        $response = $front->dispatch();

        return $response;
    }

    public function boot($skipDatabase = false)
    {
        $result = parent::boot($skipDatabase);

        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $this->getContainer()->get('models')->getRepository(Shop::class);
        $shop = $repository->getActiveDefault();
        @$shop->registerResources();

        return $result;
    }

    protected function buildContainer()
    {
        $containerBuilder = parent::buildContainer();

        $loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('test-services.xml');

        $containerBuilder->addCompilerPass(
            $this->createMakeServicesPublicCompilerPass(),
            PassConfig::TYPE_OPTIMIZE
        );

        return $containerBuilder;
    }

    protected function createMakeServicesPublicCompilerPass(): CompilerPassInterface
    {
        return new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ($container->getDefinitions() as $id => $definition) {
                    if (\mb_strpos($id, 'swag_essentials') !== 0) {
                        continue;
                    }

                    $definition->setPublic(true);
                    $definition->setShared(true);
                }
            }
        };
    }

    /**
     * Terminates a request/response cycle.
     *
     * Should be called after sending the response and before shutting down the kernel.
     *
     * @param SymfonyRequest  $request  A Request instance
     * @param SymfonyResponse $response A Response instance
     */
    public function terminate(SymfonyRequest $request, SymfonyResponse $response)
    {
        \Smarty_Resource::$sources = [];
        \Smarty_Resource::$compileds = [];
        \Enlight_Template_Manager::$_smarty_vars = [];

        $this->resetFront();
    }

    public function beforeTest()
    {
        if (\class_exists("\Zend_Session")) {
            \Zend_Session::$_unitTestEnabled = true;

            if (\Zend_Session::isStarted() && \Zend_Session::isWritable()) {
                $session = $this->getContainer()->get('session');
                $sessionId = $session->get('sessionId');
                $session->unsetAll();
                $session->offsetSet('sessionId', $sessionId);
            }
        } else {
            $session = $this->getContainer()->get('session');
            $sessionId = $session->get('sessionId');
            $session->clear();
            $session->offsetSet('sessionId', $sessionId);
        }

        $this->resetFront();
        $this->resetShopResource();
    }

    public function beforeUnset()
    {
        if (\class_exists("\Zend_Session")) {
            if (\Zend_Session::isStarted() && \Zend_Session::isWritable()) {
                $this->getContainer()->get('session')->unsetAll();
                \Zend_Session::writeClose();
            }
        } else {
            $this->getContainer()->get(SessionInterface::class)->clear();
        }

        unset($_SESSION);

        $this->getContainer()->get('dbal_connection')->close();

        \Smarty_Resource::$sources = [];
        \Smarty_Resource::$compileds = [];

        Shopware(new EmptyShopwareApplication());
    }

    public function beforeWebTest()
    {
        $this
            ->getContainer()
            ->get('plugins')
            ->Core()
            ->ErrorHandler()
            ->registerErrorHandler(E_ALL | E_STRICT);
    }

    public function afterWebTest()
    {
        \restore_error_handler();
    }

    public function authenticateApiUser()
    {
        if (\class_exists("\Zend_Session")) {
            \Zend_Session::$_unitTestEnabled = true;
        }

        $adapter = new class() implements \Zend_Auth_Adapter_Interface {
            /**
             * Performs an authentication attempt
             *
             * @throws \Zend_Auth_Adapter_Exception If authentication cannot be performed
             *
             * @return \Zend_Auth_Result
             */
            public function authenticate()
            {
                return new \Zend_Auth_Result(\Zend_Auth_Result::SUCCESS, ['id' => 1, 'username' => 'demo']);
            }
        };

        $auth = \Shopware_Components_Auth::getInstance();
        $auth->setBaseAdapter($adapter);
        $auth->addAdapter($adapter);

        $this->getContainer()->set('auth', $auth);
    }

    protected function resetFront()
    {
        $front = $this->container->get('front');

        $front->setRequest(\Enlight_Controller_Request_RequestTestCase::class);
        $front->Request()->setBaseUrl(SHOP_HOST);
        $front->setResponse(\Enlight_Controller_Response_ResponseTestCase::class);
    }

    private function resetShopResource()
    {
        $container = $this->getContainer();

        if ($container->has('shop') && $container->get('shop')->getId() === 1) {
            return;
        }

        /** @var \Shopware\Models\Shop\Repository $repository */
        $repository = $container->get('models')->getRepository(Shop::class);
        $shop = $repository->getActiveDefault();

        $container->get('shopware.components.shop_registration_service')
            ->registerResources($shop);
    }
}

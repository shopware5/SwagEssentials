<?php

namespace Doctrine\DBAL;

use SwagEssentials\PrimaryReplica\PdoDecorator;
use SwagEssentials\PrimaryReplica\ShopwareBridge;

class ShopwareConnectionWrapper extends Connection
{
    /** @var  ShopwareBridge */
    private $shopwareBridge;


    public function connect()
    {
        $return = parent::connect();

        $this->initShopwareBridge();

        // do not decorate in CLI mode or in the backend
        if (php_sapi_name() === 'cli'
            || !isset($_SERVER['REQUEST_URI'])
            || strpos($_SERVER['REQUEST_URI'], 'backend') !== false
        ) {
            return $return;
        }

        $this->decorate();

        return $return;
    }

    /**
     * Replace the current PDO connection with our decorator
     */
    private function decorate()
    {
        // do not decorate again
        if ($this->_conn instanceof PdoDecorator) {
            return;
        }

        $this->shopwareBridge->initSessionPinning();
        $this->_conn = Shopware()->Container()->get('primaryreplica.pdo_decorator');
    }

    private function initShopwareBridge()
    {
        if ($this->shopwareBridge) {
            return;
        }

        Shopware()->Loader()->registerNamespace('SwagEssentials\PrimaryReplica', __DIR__ . '/../../');

        $this->shopwareBridge = new ShopwareBridge($this->_conn);
    }
}

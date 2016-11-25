<?php

namespace SwagEssentials\Caching\Subscriber;

use Enlight\Event\SubscriberInterface;

class ShopRepository implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware\Models\Shop\Repository::getActiveById::replace' => 'onReplaceGetActiveById',
            'Shopware\Models\Shop\Repository::getActiveByRequest::replace' => 'onReplaceGetActiveByRequest'
        ];
    }

    public function onReplaceGetActiveById(\Enlight_Hook_HookArgs $args)
    {
        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_shop')) {
            return $args->getSubject()->executeParent($args->getMethod(), $args->getArgs());
        }

        $id = $args->get('id');

        $keys = ['shopById', $id];
        $hash = md5(json_encode($keys));

        return $this->returnCached($args, $hash);
    }

    public function onReplaceGetActiveByRequest(\Enlight_Hook_HookArgs $args)
    {
        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_shop')) {
            return $args->getSubject()->executeParent($args->getMethod(), $args->getArgs());
        }

        /** @var \Enlight_Controller_Request_Request $request */
        $request = $args->get('request');

        $keys = ['shopByRequest', $request->getHttpHost(), $request->getRequestUri(), $request->isSecure()];
        $hash = md5(json_encode($keys));

        return $this->returnCached($args, $hash);
    }

    /**
     * @param \Enlight_Hook_HookArgs $args
     * @param string $hash
     * @return mixed
     */
    private function returnCached(\Enlight_Hook_HookArgs $args, $hash)
    {
        /** @var \Zend_Cache_Core $cache */
        $cache = Shopware()->Cache();

        if ($result = $cache->load($hash)) {
            return $result[0];
        }

        $result = $args->getSubject()->executeParent($args->getMethod(), $args->getArgs());
        $cache->save([$result], $hash, [], Shopware()->Container()->getParameter('swag_essentials.caching_ttl_shop'));
        return $result;
    }
}

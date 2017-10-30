<?php declare(strict_types=1);

namespace SwagEssentials\Redis\ProductGateway;

use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class ListProductService implements ListProductServiceInterface
{
    /**
     * @var ListProductServiceInterface The previously existing service
     */
    private $service;

    const HASH_NAME = 'sw_list_product';

    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(ListProductServiceInterface $service, \Redis $redis)
    {
        $this->service = $service;

        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        if (empty($numbers)) {
            return [];
        }

        $keys = $this->getCacheKeys($numbers, $context);

        $redisResult = $this->redis->hMGet(self::HASH_NAME, $keys);


        $redisResult = array_combine(array_values($numbers), array_map('unserialize', $redisResult));
        $missingResults = array_keys(array_filter($redisResult, 'is_bool')) ?: [];

        if (count($missingResults) > 0) {
            $missingResults = $this->service->getList($missingResults, $context);

            $this->redis->hMset(
                self::HASH_NAME,
                array_map(
                    'serialize',
                    array_combine(
                        $this->getCacheKeys(array_keys($missingResults), $context),
                        array_values($missingResults)
                    )
                )
            );

            foreach ($missingResults as $number => $value) {
                $redisResult[$number] = $value;
            }
        }

        return $redisResult;
    }

    /**
     * {@inheritdoc}
     */
    public function get($number, Struct\ProductContextInterface $context)
    {
        $products = $this->getList([$number], $context);

        return array_shift($products);
    }

    /**
     * @param array $numbers
     * @return array
     */
    private function getCacheKeys(array $numbers, Struct\ProductContextInterface $context): array
    {
        $contextHash = md5(
            serialize(
                [
                    $context->getShop()->getId(),
                    $context->getCurrentCustomerGroup()->getId(),
                    $context->getCurrency()->getId(),
                    $context->getTaxRules(),
                ]
            )
        );

        $keys = array_map(
            function ($number) use ($contextHash) {
                return $contextHash . '.' . $number;
            },
            $numbers
        );

        return $keys;
    }
}

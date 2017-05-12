<?php

namespace SwagEssentials\RedisNumberRange;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RedisNumberRangeCommand extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('numberrange:sync')
            ->setDescription('Synchronize number ranges between redis and shopware')
            ->addOption(
                'to-redis',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'to-shopware',
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE
            )
            ->setHelp(
                <<<EOF
                The <info>%command.name%</info> command moves number ranges from shopware to redis and vice versa.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $toRedis = $input->getOption('to-redis');
        $toShopware = $input->getOption('to-shopware');
        $force = $input->getOption('force');

        $shopwareNumberRange = $this->getShopwareNumberRange();
        $redisNumberRange = $this->getRedisNumberRange();

        $redis = $this->getContainer()->get('swag_essentials.redis');

        if ($toRedis) {
            // check conflicts beforehands
            foreach ($shopwareNumberRange as $name => $value) {
                if (!$force && $redisNumberRange[$name] > $value) {
                    $output->writeln("Redis value is higher than shopware's value. Use --force to import anyway");
                    exit(1);
                }
            }

            foreach ($shopwareNumberRange as $name => $value) {
                $output->write("Setting $name to $value");
                $redis->hset(RedisNumberRangeIncrementer::HASH_NAME, $name, $value);
                $output->writeln(" ✓");
            }
        }

        if ($toShopware) {
            // check conflicts beforehands
            foreach ($redisNumberRange as $name => $value) {
                if (!$force && $shopwareNumberRange[$name] > $value) {
                    $output->writeln("Shopware value is higher than redis's value. Use --force to import anyway");
                    exit(1);
                }
            }

            foreach ($redisNumberRange as $name => $value) {
                $output->write("Setting $name to $value");
                $this->setShopwareKey($name, $value);
                $output->writeln(" ✓");
            }
        }

    }

    private function setShopwareKey($key, $value)
    {
        $stmt = $this->getContainer()->get('dbal_connection')->prepare(
            'REPLACE INTO s_order_number SET `name` = :key, `number` = :value'
        );

        $stmt->execute(['key' => $key, 'value' => $value]);
    }

    private function getRedisNumberRange()
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get('swag_essentials.redis');

        return $redis->hGetAll(RedisNumberRangeIncrementer::HASH_NAME);
    }

    private function getShopwareNumberRange()
    {
        $result = $this->getContainer()->get('dbal_connection')->fetchAll(
            'SELECT `name`, `number` FROM s_order_number'
        );

        $numbers = [];

        foreach ($result as $row) {
            $numbers[$row['name']] = $row['number'];
        }

        return $numbers;
    }
}
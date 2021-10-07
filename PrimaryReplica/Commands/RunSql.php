<?php

declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunSql extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:run')
            ->setDescription('Run a SQL command on the given database connection')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The connection you want to run the query on'
            )
            ->addArgument(
                'sql',
                InputArgument::REQUIRED,
                'The SQL query you want to run. Quoting with " is recommended'
            )
            ->setHelp(
                <<<EOF
<info>%command.name%</info> will allow you to run a query on multiple connections simultaneously
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $names = $input->getOption('connection');
        $sql = $input->getArgument('sql');

        $isReadQuery = stripos($sql, 'SELECT') !== false;

        foreach ($names as $name) {
            $output->writeln("<error>Handling $name</error>");
            /** @var \PDO $connection */
            $connection = $this->getContainer()->get('primaryreplica.connection_pool')->getConnectionByName($name);

            if ($isReadQuery) {
                $stmt = $connection->query($sql);
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $table = new Table($output);
                $table->setHeaders(array_keys($result[0]))->setRows($result);
                $table->render();
            } else {
                $connection->exec($sql);
            }
        }
    }
}

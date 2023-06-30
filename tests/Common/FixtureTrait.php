<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Doctrine\DBAL\Connection;

trait FixtureTrait
{
    /**
     * @var bool
     */
    protected $commonFixturesImported = false;

    /**
     * @var bool
     */
    protected $disableCommonFixtures = false;

    /**
     * @var array
     */
    protected $commonTestFixtures = [
        __DIR__ . '/../../common_test_fixtures.sql',
    ];

    /**
     * @var array
     */
    protected $importedFiles = [];

    /**
     * @after
     */
    protected function cleanUpFixtureTraitState()
    {
        $this->disableCommonFixtures(false);
        $this->commonFixturesImported = false;
    }

    public function disableCommonFixtures(bool $set = true)
    {
        $this->disableCommonFixtures = $set;
    }

    protected function importFixturesFileOnce(string $filePath)
    {
        if (!\file_exists($filePath)) {
            throw new \InvalidArgumentException('Could not find file ' . $filePath);
        }

        if (\array_key_exists($filePath, $this->importedFiles)) {
            return;
        }

        $this->importedFiles[$filePath] = true;

        $sql = \file_get_contents($filePath);

        if (!$sql) {
            return;
        }

        $this->importFixtures($sql);
    }

    public function importFixtures(string $sql)
    {
        $defaultSql = $this->loadCommonFixtureSql();

        if ($defaultSql) {
            $sql = $defaultSql . $sql;
        }

        /** @var Connection $connection */
        $connection = self::getKernel()->getContainer()->get('dbal_connection');

        $sqlStatements = \explode(";\n", $sql);

        foreach ($sqlStatements as $sqlStatement) {
            if (!\trim($sqlStatement)) {
                continue;
            }

            $connection->exec(\trim($sqlStatement));
        }

        if (!(int) $connection->errorCode()) {
            return;
        }

        throw new \Exception('unable to import fixtures ' . \print_r($connection->errorInfo(), true));
    }

    protected function loadCommonFixtureSql(): string
    {
        $sql = '';

        if (!$this->commonFixturesImported && !$this->disableCommonFixtures) {
            $this->commonFixturesImported = true;
            $this->disableCommonFixtures(false);
            foreach ($this->commonTestFixtures as $commonFile) {
                $sql .= \file_get_contents($commonFile);
            }
        }

        return $sql;
    }
}

<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use function array_key_exists;
use Doctrine\DBAL\Connection;
use Exception;
use function explode;
use function file_exists;
use function file_get_contents;
use InvalidArgumentException;
use function print_r;
use function trim;

trait FixtureTrait
{
    /**
     * @var bool
     */
    private $commonFixturesImported = false;

    /**
     * @var bool
     */
    private $disableCommonFixtures = false;

    /**
     * @var array
     */
    private $commonTestFixtures = [
        __DIR__ . '/../../common_test_fixtures.sql',
    ];

    /**
     * @var array
     */
    private $importedFiles = [];

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
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Could not find file ' . $filePath);
        }

        if (array_key_exists($filePath, $this->importedFiles)) {
            return;
        }

        $this->importedFiles[$filePath] = true;

        $sql = file_get_contents($filePath);

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

        $sqlStatements = explode(";\n", $sql);

        foreach ($sqlStatements as $sqlStatement) {
            if (!trim($sqlStatement)) {
                continue;
            }

            $connection->exec(trim($sqlStatement));
        }

        if (!(int) $connection->errorCode()) {
            return;
        }

        throw new Exception('unable to import fixtures ' . print_r($connection->errorInfo(), true));
    }

    private function loadCommonFixtureSql(): string
    {
        $sql = '';

        if (!$this->commonFixturesImported && !$this->disableCommonFixtures) {
            $this->commonFixturesImported = true;
            $this->disableCommonFixtures(false);
            foreach ($this->commonTestFixtures as $commonFile) {
                $sql .= file_get_contents($commonFile);
            }
        }

        return $sql;
    }
}

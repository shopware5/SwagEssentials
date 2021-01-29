<?php declare(strict_types=1);

namespace SwagEssentials\Tests\PrimaryReplica\Unit;

use PHPUnit\Framework\TestCase;
use SwagEssentials\PrimaryReplica\ConnectionDecision;
use SwagEssentials\PrimaryReplica\ConnectionPool;

class ConnectionDecisionTest extends TestCase
{
    public function sqlDataProvider()
    {
        return [
            ['SELECT * FROM s_user', false],
            ['SELECT * FROM s_user FOR UPDATE', true],
            ['UPDATE s_user SET password = "foo"', true],
            [' UPDATE s_user SET password = "foo"', true],
            ['DELETE FROM s_user WHERE 1', true],
            ['  INSERT INTO …', true],
            [' INSERT INTO …', true],
            ['SHOW TABLES', false],
            ['BEGIN TRANSACTION', true],
            ['START TRANSACTION', true],
            ['ROLLBACK', true],
            ['COMMIT', true],
            ['COMMIT', true],
            ['DESCRIBE s_user', false],
            ['DESC s_user', false],
            ['EXPLAIN s_user', false],
            ['EXPLAIN SELECT * FROM s_articles', false],
        ];
    }

    /**
     * @dataProvider sqlDataProvider
     * @param mixed $query
     * @param mixed $isWriteQuery
     */
    public function testIsWriteQuery($query, $isWriteQuery)
    {
        $connectionDecision = $this->createMock(ConnectionDecision::class);

        $result = $this->invokeMethod($connectionDecision, 'isWriteQuery', [$query]);
        static::assertEquals($isWriteQuery, $result);
    }

    public function testGetTables()
    {
        $connectionPool = $this->getConnectionPoolMock();

        $connectionDecision = new ConnectionDecision($connectionPool, []);
        $result = $this->invokeMethod($connectionDecision, 'getTables');

        static::assertEquals('s_articles|s_user', $result);
    }

    public function testGetAffectedTables()
    {
        $connectionPool = $this->getConnectionPoolMock();

        $connectionDecision = new ConnectionDecision($connectionPool, []);
        $result = $this->invokeMethod($connectionDecision, 'getAffectedTables', ['SELECT * FROM s_articles, s_articles_details']);

        static::assertEquals(['s_articles', 's_articles_details'], $result);
    }

    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConnectionPoolMock()
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->method('getRandomConnection')->willReturnCallback(
            function () {
                $pdo = $this->createMock(\PDO::class);
                $pdo->method('query')->willReturnCallback(
                    function () {
                        $stmt = $this->createMock(\PDOStatement::class);
                        $stmt->method('fetchAll')->willReturn(
                            ['s_articles', 's_user', 's_user_billing']
                        );

                        return $stmt;
                    }
                );

                return [
                    'primary',
                    $pdo,
                ];
            }
        );

        return $connectionPool;
    }
}

<?php

use PHPUnit\Framework\TestCase;

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
     */
    public function testIsWriteQuery($query, $isWriteQuery)
    {
        $connectionDecision = $this->createMock(\SwagEssentials\PrimaryReplica\ConnectionDecision::class);

        $result = $this->invokeMethod($connectionDecision, 'isWriteQuery', [$query]);
        $this->assertEquals($isWriteQuery, $result);
    }

    public function testGetTables()
    {
        $connectionPool = $this->getConnectionPoolMock();

        $connectionDecision = new \SwagEssentials\PrimaryReplica\ConnectionDecision($connectionPool, []);
        $result = $this->invokeMethod($connectionDecision, 'getTables');

        $this->assertEquals('s_articles|s_user', $result);
    }

    public function testGetAffectedTables()
    {
        $connectionPool = $this->getConnectionPoolMock();

        $connectionDecision = new \SwagEssentials\PrimaryReplica\ConnectionDecision($connectionPool, []);
        $result = $this->invokeMethod($connectionDecision, 'getAffectedTables',  ['SELECT * FROM s_articles, s_articles_details']);

        $this->assertEquals(['s_articles', 's_articles_details'], $result);
    }

    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getConnectionPoolMock()
    {
        $connectionPool = $this->createMock(\SwagEssentials\PrimaryReplica\ConnectionPool::class);
        $connectionPool->method('getRandomConnection')->willReturnCallback(
            function () {
                $pdo = $this->createMock(Pdo::class);
                $pdo->method('query')->willReturnCallback(
                    function () {
                        $stmt = $this->createMock(PDOStatement::class);
                        $stmt->method('fetchAll')->willReturn(
                            ['s_articles', 's_user', 's_user_billing']
                        );
                        return $stmt;
                    }
                );
                return [
                    'primary',
                    $pdo
                ];
            }
        );
        return $connectionPool;
    }
}
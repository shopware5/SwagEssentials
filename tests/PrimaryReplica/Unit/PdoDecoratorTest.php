<?php

declare(strict_types=1);

namespace SwagEssentials\Tests\PrimaryReplica\Unit;

use Doctrine\DBAL\Driver\PDO\Statement as PDODriverStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SwagEssentials\PrimaryReplica\ConnectionDecision;
use SwagEssentials\PrimaryReplica\ConnectionPool;
use SwagEssentials\PrimaryReplica\PdoDecorator;

class PdoDecoratorTest extends TestCase
{
    public function testAppliesAttributesToAllConnectionsAndReturnsTrue(): void
    {
        $connectionPoolMock = $this->getConnectionPoolMock(true, true);
        $connectionDecisionMock = $this->createMock(ConnectionDecision::class);

        $pdoDecorator = new PdoDecorator($connectionDecisionMock, $connectionPoolMock);
        static::assertTrue($pdoDecorator->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PDODriverStatement::class, []]));
    }

    public function testAppliesAttributesToAllConnectionsButOneFails(): void
    {
        $connectionPoolMock = $this->getConnectionPoolMock(true, false);
        $connectionDecisionMock = $this->createMock(ConnectionDecision::class);

        $pdoDecorator = new PdoDecorator($connectionDecisionMock, $connectionPoolMock);
        static::assertFalse($pdoDecorator->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PDODriverStatement::class, []]));
    }

    public function testAppliesAttributesToAllConnectionsButAllFails(): void
    {
        $connectionPoolMock = $this->getConnectionPoolMock(false, false);
        $connectionDecisionMock = $this->createMock(ConnectionDecision::class);

        $pdoDecorator = new PdoDecorator($connectionDecisionMock, $connectionPoolMock);
        static::assertFalse($pdoDecorator->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [PDODriverStatement::class, []]));
    }

    /**
     * @return MockObject&ConnectionPool
     */
    protected function getConnectionPoolMock(bool $returnOne, bool $returnTwo): MockObject
    {
        $pdo = $this->createMock(\PDO::class);

        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects(static::exactly(1))->method('setAttribute');
        $connectionPool->method('getRandomConnection')->willReturn(['primary', $pdo]);

        $connectionPool->method('getActiveConnections')->willReturnCallback(
            function () use ($returnOne, $returnTwo) {
                $pdoOne = $this->createMock(\PDO::class);
                $pdoOne->expects($this->exactly(1))->method('setAttribute')->willReturn($returnOne);

                $pdoTwo = $this->createMock(\PDO::class);
                $pdoTwo->expects($this->exactly(1))->method('setAttribute')->willReturn($returnTwo);

                return [
                    $pdoOne,
                    $pdoTwo,
                ];
            }
        );

        return $connectionPool;
    }
}

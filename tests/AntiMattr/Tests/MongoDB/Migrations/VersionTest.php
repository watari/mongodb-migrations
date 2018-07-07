<?php
declare(strict_types = 1);

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\MongoDB\Migrations\Version;
use Doctrine\MongoDB\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Doctrine\MongoDB\Database;
use MongoDB\BSON\UTCDateTime;

class VersionTest extends TestCase
{
    private $className;
    /** @var ConfigurationInterface|MockObject */
    private $configuration;
    private $connection;
    private $db;
    /** @var Version|MockObject */
    private $migration;
    /** @var VersionStub */
    private $version;
    private $versionName;
    /** @var OutputWriter|MockObject */
    private $outputWriter;
    /** @var Statistics|MockObject */
    private $statistics;

    protected function setUp()
    {
        $this->className = 'AntiMattr\Tests\MongoDB\Migrations\Version20140908000000';
        $this->configuration = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->connection = $this->createMock('Doctrine\MongoDB\Connection');
        $this->db = $this->createMock('Doctrine\MongoDB\Database');
        $this->migration = $this->createMock('AntiMattr\Tests\MongoDB\Migrations\Version20140908000000');
        $this->outputWriter = $this->createMock('AntiMattr\MongoDB\Migrations\OutputWriter');
        $this->statistics = $this->createMock('AntiMattr\MongoDB\Migrations\Collection\Statistics');
        $this->versionName = '20140908000000';

        $this->configuration->expects($this->once())
                            ->method('getOutputWriter')
                            ->will($this->returnValue($this->outputWriter));
        $this->configuration->expects($this->once())
                            ->method('getConnection')
                            ->will($this->returnValue($this->connection));
        $this->configuration->expects($this->once())
                            ->method('getDatabase')
                            ->will($this->returnValue($this->db));

        $this->version = new VersionStub(
            $this->configuration,
            Configuration::DEFAULT_PREFIX,
            $this->versionName,
            $this->className
        );
        $this->version->setStatistics($this->statistics);
        $this->version->setMigration($this->migration);
    }

    public function testConstructor()
    {
        $this->assertSame($this->configuration, $this->version->getConfiguration());
        $this->assertSame(Version::STATE_NONE, $this->version->getState());
        $this->assertSame($this->versionName, $this->version->getVersion());
        $this->assertEquals($this->versionName, (string)$this->version);
        $this->assertNotNull($this->version->getMigration());
    }

    public function testAnalyzeThrowsException()
    {
        /** @var Collection|MockObject $collection */
        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
                         ->method('setCollection')
                         ->with($collection);

        $collection->expects($this->once())
                   ->method('getName')
                   ->will($this->returnValue('test_name'));

        $expectedException = new \RuntimeException();

        $this->statistics->expects($this->once())
                         ->method('updateBefore')
                         ->will($this->throwException($expectedException));

        $this->outputWriter->expects($this->once())
                           ->method('write');

        $this->version->analyze($collection);
    }

    public function testAnalyze()
    {
        /** @var Collection|MockObject $collection */
        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
                         ->method('setCollection')
                         ->with($collection);

        $collection->expects($this->once())
                   ->method('getName')
                   ->will($this->returnValue('test_name'));

        $this->statistics->expects($this->once())
                         ->method('updateBefore');

        $this->outputWriter->expects($this->never())
                           ->method('write');

        $this->version->analyze($collection);
    }

    public function testMarkMigrated()
    {
        $timestamp = new UTCDateTime();
        $this->version->setTimestamp($timestamp);

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
                            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
                            ->method('getCollection')
                            ->will($this->returnValue($collection));

        $insert = [
            'v' => $this->versionName,
            't' => $timestamp,
            'prefix' => Configuration::DEFAULT_PREFIX,
        ];

        $collection->expects($this->once())
                   ->method('insert')
                   ->with($insert);

        $this->version->markMigrated();
    }

    public function testMarkMigratedWithReplay()
    {
        $timestamp = new UTCDateTime();
        $this->version->setTimestamp($timestamp);

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
                            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
                            ->method('getCollection')
                            ->will($this->returnValue($collection));

        $query = [
            'v' => $this->versionName,
            'prefix' => Configuration::DEFAULT_PREFIX,

        ];

        $update = [
            'v' => $this->versionName,
            't' => $timestamp,
            'prefix' => Configuration::DEFAULT_PREFIX,
        ];

        $collection->expects($this->once())
                   ->method('update')
                   ->with($query, $update);

        $replay = true;
        $this->version->markMigrated($replay);
    }

    public function testMarkNotMigrated()
    {
        $timestamp = new UTCDateTime();
        $this->version->setTimestamp($timestamp);

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
                            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
                            ->method('getCollection')
                            ->will($this->returnValue($collection));

        $remove = [
            'v' => $this->versionName,
            'prefix' => Configuration::DEFAULT_PREFIX,
        ];

        $collection->expects($this->once())
                   ->method('remove')
                   ->with($remove);

        $this->version->markNotMigrated();
    }

    public function testUpdateStatisticsAfterThrowsException()
    {
        /** @var Collection|MockObject $collection */
        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
                         ->method('setCollection')
                         ->with($collection);

        $expectedException = new \RuntimeException();

        $this->statistics->expects($this->once())
                         ->method('updateAfter')
                         ->will($this->throwException($expectedException));

        $this->outputWriter->expects($this->once())
                           ->method('write');

        $this->version->analyze($collection);
        $this->version->doUpdateStatisticsAfter();
    }

    public function testUpdateStatisticsAfter()
    {
        /** @var Collection|MockObject $collection */
        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
                         ->method('setCollection')
                         ->with($collection);

        $this->statistics->expects($this->once())
                         ->method('getCollection')
                         ->will($this->returnValue($collection));

        $collection->expects($this->exactly(2))
                   ->method('getName')
                   ->will($this->returnValue('test_name'));

        $this->statistics->expects($this->once())
                         ->method('updateAfter');

        $this->outputWriter->expects($this->never())
                           ->method('write');

        $this->version->analyze($collection);
        $this->version->doUpdateStatisticsAfter();
    }

    public function testIsMigrated()
    {
        $this->configuration->expects($this->once())
                            ->method('hasVersionMigrated')
                            ->with($this->version);

        $this->version->isMigrated();
    }

    /**
     * @test
     *
     * testExecuteDownWithReplayThrowsException
     *
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\AbortException
     */
    public function testExecuteDownWithReplayThrowsException()
    {
        // These methods will not be called
        $this->migration->expects($this->never())->method('down');
        $this->configuration->expects($this->never())
                            ->method('createMigrationCollection');
        $this->configuration->expects($this->never())
                            ->method('getCollection');

        $replay = true;
        $this->version->execute('down', $replay);
    }

    /**
     * @dataProvider provideDirection
     */
    public function testExecuteThrowsSkipException($direction)
    {
        $expectedException = new \AntiMattr\MongoDB\Migrations\Exception\SkipException();

        $this->migration->expects($this->once())
                        ->method($direction)
                        ->will($this->throwException($expectedException));

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
                            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
                            ->method('getCollection')
                            ->will($this->returnValue($collection));

        $this->version->execute($direction);
    }

    /**
     * @dataProvider provideDirection
     */
    public function testExecute($direction)
    {
        $this->migration->expects($this->once())
                        ->method('pre' . $direction);

        $this->migration->expects($this->once())
                        ->method($direction);

        $this->migration->expects($this->once())
                        ->method('post' . $direction);

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
                            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
                            ->method('getCollection')
                            ->will($this->returnValue($collection));

        $this->version->execute($direction);
    }

    public function provideDirection()
    {
        return [
            ['up'],
            ['down'],
        ];
    }
}

class VersionStub extends Version
{
    private $statistics;
    private $timestamp;

    public function doUpdateStatisticsAfter()
    {
        $this->updateStatisticsAfter();
    }

    public function getState()
    {
        return $this->state;
    }

    public function setMigration($migration)
    {
        $this->migration = $migration;
    }

    public function setStatistics($statistics)
    {
        $this->statistics = $statistics;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    protected function createStatistics()
    {
        return $this->statistics;
    }

    protected function createMigration()
    {
        return $this->migration;
    }

    protected function createMongoTimestamp()
    {
        return $this->timestamp;
    }
}

class Version20140908000000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Test Version';
    }

    public function up(Database $db)
    {
    }

    public function down(Database $db)
    {
    }
}

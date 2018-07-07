<?php
declare(strict_types = 1);

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\Version;
use Doctrine\MongoDB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    /** @var ConfigurationInterface */
    private $configuration;
    /** @var Connection|MockObject */
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->configuration = new Configuration($this->connection);
        $this->configuration->setPrefix(Configuration::DEFAULT_PREFIX);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->connection, $this->configuration->getConnection());
        $this->assertEmpty($this->configuration->getMigrations());
        $this->assertEmpty($this->configuration->getAvailableVersions());
    }

    public function testGetCollection()
    {
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');
        $this->configuration->setMigrationsCollectionName('antimattr_migration_versions_test');

        $expectedCollection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($expectedCollection);

        $collection = $this->configuration->getCollection();
        $this->assertEquals($expectedCollection, $collection);
    }

    public function testGetCurrentVersion()
    {
        $this->prepareValidConfiguration();

        $directory = \dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->registerMigrationsFromDirectory($directory);

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $cursor = $this->createMock('Doctrine\MongoDB\Cursor');

        $in = [
            'v' => ['$in' => ['20140822185742', '20140822185743', '20140822185744']],
            'prefix' => Configuration::DEFAULT_PREFIX,
        ];

        $collection->expects($this->once())
                   ->method('find')
                   ->with($in)
                   ->willReturn($cursor);

        $cursor->expects($this->once())
               ->method('sort')
               ->with(['v' => -1])
               ->willReturn($cursor);

        $cursor->expects($this->once())
               ->method('limit')
               ->with(1)
               ->willReturn($cursor);

        $cursor->expects($this->once())
               ->method('getNext')
               ->willReturn(['v' => '20140822185743']);

        $version = $this->configuration->getCurrentVersion();

        $this->assertEquals($version, '20140822185743');
    }

    public function testGetDatabase()
    {
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');

        $expectedDatabase = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($expectedDatabase);

        $database = $this->configuration->getDatabase();
        $this->assertEquals($expectedDatabase, $database);
    }

    public function testGetMigratedVersions()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $foundVersions = [
            ['v' => 'found1'],
            ['v' => 'found2'],
        ];

        $expectedVersions = [
            'found1',
            'found2',
        ];

        $collection->expects($this->once())
                   ->method('find')
                   ->willReturn($foundVersions);

        $versions = $this->configuration->getMigratedVersions();
        $this->assertEquals($expectedVersions, $versions);
    }

    public function testGetNumberOfExecutedMigrations()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $cursor = $this->createMock('Doctrine\MongoDB\Cursor');

        $collection->expects($this->once())
                   ->method('find')
                   ->willReturn($cursor);

        $cursor->expects($this->once())
               ->method('count')
               ->willReturn(2);

        $this->assertEquals(2, $this->configuration->getNumberOfExecutedMigrations());
    }

    public function testRegisterMigrationsFromDirectory()
    {
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->assertFalse($this->configuration->hasVersion('20140822185742'));

        $directory = \dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->registerMigrationsFromDirectory($directory);

        $this->assertEquals(3, \count($this->configuration->getMigrations()));
        $this->assertEquals(3, \count($this->configuration->getAvailableVersions()));
        $this->assertEquals(3, $this->configuration->getNumberOfAvailableMigrations());

        $this->assertTrue($this->configuration->hasVersion('20140822185742'));

        $this->configuration->getVersion('20140822185742');
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     */
    public function testGetVersionThrowsUnknownVersionException()
    {
        $this->configuration->getVersion('20140822185742');
    }

    public function testHasVersionMigrated()
    {
        /** @var Version|MockObject $version1 */
        $version1 = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        /** @var Version|MockObject $version2 */
        $version2 = $this->createMock('AntiMattr\MongoDB\Migrations\Version');

        $this->prepareValidConfiguration();

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $version1->expects($this->once())
                 ->method('getVersion')
                 ->willReturn('found');

        $version2->expects($this->once())
                 ->method('getVersion')
                 ->willReturn('found2');

        $collection->expects($this->at(1))
                   ->method('findOne')
                   ->with(['v' => 'found', 'prefix' => Configuration::DEFAULT_PREFIX])
                   ->willReturn('foo');

        $collection->expects($this->at(2))
                   ->method('findOne')
                   ->with(['v' => 'found2', 'prefix' => Configuration::DEFAULT_PREFIX])
                   ->willReturn(null);

        $this->assertTrue($this->configuration->hasVersionMigrated($version1));
        $this->assertFalse($this->configuration->hasVersionMigrated($version2));
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function testValidateThrowsConfigurationValidationException()
    {
        $this->configuration->validate();
    }

    public function testGetUnavailableMigratedVersions()
    {
        /** @var ConfigurationInterface|MockObject $configuration */
        $configuration = $this->getMockBuilder('AntiMattr\MongoDB\Migrations\Configuration\Configuration')
                              ->disableOriginalConstructor()
                              ->setMethods(['getMigratedVersions', 'getAvailableVersions'])
                              ->getMock();
        $configuration->expects($this->once())
                      ->method('getMigratedVersions')
                      ->willReturn(['1', '2']);
        $configuration->expects($this->once())
                      ->method('getAvailableVersions')
                      ->willReturn(['2', '3']);

        $this->assertEquals(['1'], $configuration->getUnavailableMigratedVersions());
    }

    public function testValidate()
    {
        $this->prepareValidConfiguration();
        $this->configuration->validate();
        self::assertTrue(true); // All checks is passes, no exceptions is thrown.
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Unexpected duplicate version records in the database
     */
    public function testDuplicateInGetMigratedTimestampThrowsException()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $cursor = $this->createMock('Doctrine\MongoDB\Cursor');

        $collection->expects($this->once())
                   ->method('find')
                   ->willReturn($cursor);

        $cursor->expects($this->exactly(2))
               ->method('count')
               ->willReturn(2);

        $this->configuration->getMigratedTimestamp('1');
    }

    public function testGetMigratedTimestamp()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('Doctrine\MongoDB\Collection');
        $database = $this->createMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
                         ->method('selectDatabase')
                         ->with('test_antimattr_migrations')
                         ->willReturn($database);

        $database->expects($this->once())
                 ->method('selectCollection')
                 ->with('antimattr_migration_versions_test')
                 ->willReturn($collection);

        $cursor = $this->createMock('Doctrine\MongoDB\Cursor');

        $collection->expects($this->once())
                   ->method('find')
                   ->willReturn($cursor);

        $cursor->expects($this->exactly(2))
               ->method('count')
               ->willReturn(1);

        $cursor->expects($this->once())
               ->method('getNext')
               ->willReturn(['t' => new \DateTime()]);

        $this->assertTrue(\is_numeric($this->configuration->getMigratedTimestamp('1')));
    }

    private function prepareValidConfiguration()
    {
        $directory = \dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');
        $this->configuration->setMigrationsDirectory($directory);
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->configuration->setMigrationsCollectionName('antimattr_migration_versions_test');
    }
}

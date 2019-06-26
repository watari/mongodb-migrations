<?php
declare(strict_types = 1);

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\ConfigurationBuilder;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use Doctrine\MongoDB\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurationBuilderTest extends TestCase
{
    public function testBuildingConfiguration(): void
    {
        /** @var Connection|MockObject $conn */
        $conn = $this->createMock('Doctrine\MongoDB\Connection');
        $outputWriter = new OutputWriter();
        $onDiskConfig = '';

        $config = ConfigurationBuilder::create()
                                      ->setConnection($conn)
                                      ->setOutputWriter($outputWriter)
                                      ->setOnDiskConfiguration($onDiskConfig)
                                      ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());
    }

    public function testBuildingWithYamlConfig(): void
    {
        /** @var Connection|MockObject $conn */
        $conn = $this->createMock('Doctrine\MongoDB\Connection');
        $outputWriter = new OutputWriter();
        $onDiskConfig = \dirname(__DIR__) . '/Resources/fixtures/config.yml';

        $config = ConfigurationBuilder::create()
                                      ->setConnection($conn)
                                      ->setOutputWriter($outputWriter)
                                      ->setOnDiskConfiguration($onDiskConfig)
                                      ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());

        $this->assertEquals(
            './tests/AntiMattr/Tests/MongoDB/Migrations/Resources/Migrations',
            $config->getMigrationsDirectory()
        );
        $this->assertEquals('Example\Migrations\TestAntiMattr\MongoDB', $config->getMigrationsNamespace());
        $this->assertEquals('AntiMattr Sandbox Migrations', $config->getName());
        $this->assertEquals('antimattr_migration_versions_test', $config->getMigrationsCollectionName());
        $this->assertEquals('test_antimattr_migrations', $config->getMigrationsDatabaseName());
        $this->assertEquals('./bin', $config->getMigrationsScriptDirectory());
    }

    public function testBuildingWithXmlConfig()
    {
        /** @var Connection|MockObject $conn */
        $conn = $this->createMock('Doctrine\MongoDB\Connection');
        $outputWriter = new OutputWriter();
        $onDiskConfig = \dirname(__DIR__) . '/Resources/fixtures/config.xml';

        $config = ConfigurationBuilder::create()
                                      ->setConnection($conn)
                                      ->setOutputWriter($outputWriter)
                                      ->setOnDiskConfiguration($onDiskConfig)
                                      ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());

        $this->assertEquals(
            './tests/AntiMattr/Tests/MongoDB/Migrations/Resources/Migrations',
            $config->getMigrationsDirectory()
        );
        $this->assertEquals('Example\Migrations\TestAntiMattr\MongoDB', $config->getMigrationsNamespace());
        $this->assertEquals('AntiMattr Sandbox Migrations', $config->getName());
        $this->assertEquals('antimattr_migration_versions_test', $config->getMigrationsCollectionName());
        $this->assertEquals('test_antimattr_migrations', $config->getMigrationsDatabaseName());
        $this->assertEquals('./bin', $config->getMigrationsScriptDirectory());
    }
}

<?php
declare(strict_types = 1);

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\StatusCommand;
use AntiMattr\MongoDB\Migrations\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class StatusCommandTest extends TestCase
{
    /** @var StatusCommandStub */
    private $command;
    /** @var OutputInterface|MockObject */
    private $output;
    /** @var OutputFormatterInterface|MockObject */
    private $outputFormatter;
    /** @var ConfigurationInterface|MockObject */
    private $config;
    /** @var AbstractMigration|MockObject */
    private $migration;
    /** @var Version|MockObject */
    private $version;
    /** @var Version|MockObject */
    private $version2;

    protected function setUp()
    {
        $this->command = new StatusCommandStub();
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->outputFormatter = $this->createMock(
            'Symfony\Component\Console\Formatter\OutputFormatterInterface'
        );
        $this->config = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->migration = $this->createMock('AntiMattr\MongoDB\Migrations\AbstractMigration');
        $this->version = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        $this->version->expects($this->any())
                      ->method('getMigration')
                      ->will($this->returnValue($this->migration));

        $this->version2 = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        $this->version2->expects($this->any())
                       ->method('getMigration')
                       ->will($this->returnValue($this->migration));

        $this->command->setMigrationConfiguration($this->config);
    }

    public function testExecuteWithoutShowingVersions()
    {
        $input = new ArgvInput(
            [
                StatusCommand::NAME,
            ]
        );

        $configName = 'config-name';
        $databaseDriver = 'MongoDB';
        $migrationsDatabaseName = ' migrations-database-name';
        $migrationsCollectionName = 'migrations-collection-name';
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'migrations-directory';
        $currentVersion = 'abcdefghijk';
        $latestVersion = '1234567890';
        $numExecutedMigrations = 0;
        $numExecutedUnavailableMigrations = 0;
        $numAvailableMigrations = 0;
        $numNewMigrations = 0;

        // Expectations
        $this->config->expects($this->once())
                     ->method('getDetailsMap')
                     ->will(
                         $this->returnValue(
                             [
                                 'name' => $configName,
                                 'database_driver' => $databaseDriver,
                                 'migrations_database_name' => $migrationsDatabaseName,
                                 'migrations_collection_name' => $migrationsCollectionName,
                                 'migrations_namespace' => $migrationsNamespace,
                                 'migrations_directory' => $migrationsDirectory,
                                 'current_version' => $currentVersion,
                                 'latest_version' => $latestVersion,
                                 'num_executed_migrations' => $numExecutedMigrations,
                                 'num_executed_unavailable_migrations' => $numExecutedUnavailableMigrations,
                                 'num_available_migrations' => $numAvailableMigrations,
                                 'num_new_migrations' => $numNewMigrations,
                             ]
                         )
                     );
        $this->output->expects($this->at(0))
                     ->method('writeln')
                     ->with(
                         "\n <info>==</info> Configuration\n"
                     );
        $this->output->expects($this->at(1))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Name',
                             $configName
                         )
                     );
        $this->output->expects($this->at(2))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Database Driver',
                             'MongoDB'
                         )
                     );
        $this->output->expects($this->at(3))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Database Name',
                             $migrationsDatabaseName
                         )
                     );
        $this->output->expects($this->at(4))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Configuration Source',
                             'manually configured'
                         )
                     );
        $this->output->expects($this->at(5))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Version Collection Name',
                             $migrationsCollectionName
                         )
                     );
        $this->output->expects($this->at(6))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Migrations Namespace',
                             $migrationsNamespace
                         )
                     );
        $this->output->expects($this->at(7))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Migrations Directory',
                             $migrationsDirectory
                         )
                     );
        $this->output->expects($this->at(8))// current version formatted
                     ->method('writeln');
        $this->output->expects($this->at(9))// latest version formatted
                     ->method('writeln');
        $this->output->expects($this->at(10))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Executed Migrations',
                             $numExecutedMigrations
                         )
                     );
        $this->output->expects($this->at(11))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Executed Unavailable Migrations',
                             $numExecutedUnavailableMigrations
                         )
                     );
        $this->output->expects($this->at(12))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Available Migrations',
                             $numAvailableMigrations
                         )
                     );
        $this->output->expects($this->at(13))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'New Migrations',
                             $numNewMigrations
                         )
                     );

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testExecuteWithShowingVersions()
    {
        $input = new ArgvInput(
            [
                StatusCommand::NAME,
                '--show-versions',
            ]
        );

        $configName = 'config-name';
        $databaseDriver = 'MongoDB';
        $migrationsDatabaseName = ' migrations-database-name';
        $migrationsCollectionName = 'migrations-collection-name';
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'migrations-directory';
        $currentVersion = 'abcdefghijk';
        $latestVersion = '1234567890';
        $numExecutedMigrations = 2;
        $numExecutedUnavailableMigrations = 1;
        $numAvailableMigrations = 2;
        $numNewMigrations = 1;
        $notMigratedVersion = '20140822185743';
        $migratedVersion = '20140822185745';
        $unavailableMigratedVersion = '20140822185744';

        // Expectations
        $this->output
            ->method('getFormatter')
            ->will($this->returnValue($this->outputFormatter));

        $this->version->expects($this->exactly(2))
                      ->method('getVersion')
                      ->will($this->returnValue($notMigratedVersion));

        $this->version2->expects($this->exactly(3))
                       ->method('getVersion')
                       ->will($this->returnValue($migratedVersion));

        $this->migration
            ->method('getDescription')
            ->will($this->returnValue('drop all'));

        $this->config->expects($this->once())
                     ->method('getDetailsMap')
                     ->will(
                         $this->returnValue(
                             [
                                 'name' => $configName,
                                 'database_driver' => $databaseDriver,
                                 'migrations_database_name' => $migrationsDatabaseName,
                                 'migrations_collection_name' => $migrationsCollectionName,
                                 'migrations_namespace' => $migrationsNamespace,
                                 'migrations_directory' => $migrationsDirectory,
                                 'current_version' => $currentVersion,
                                 'latest_version' => $latestVersion,
                                 'num_executed_migrations' => $numExecutedMigrations,
                                 'num_executed_unavailable_migrations' => $numExecutedUnavailableMigrations,
                                 'num_available_migrations' => $numAvailableMigrations,
                                 'num_new_migrations' => $numNewMigrations,
                             ]
                         )
                     );
        $this->config->expects($this->once())
                     ->method('getUnavailableMigratedVersions')
                     ->will(
                         $this->returnValue(
                             [$unavailableMigratedVersion]
                         )
                     );
        $this->config->expects($this->once())
                     ->method('getMigrations')
                     ->will(
                         $this->returnValue(
                             [$this->version, $this->version2]
                         )
                     );
        $this->config->expects($this->once())
                     ->method('getMigratedVersions')
                     ->will(
                         $this->returnValue(
                             [$unavailableMigratedVersion, $migratedVersion]
                         )
                     );

        $this->output->expects($this->at(0))
                     ->method('writeln')
                     ->with(
                         "\n <info>==</info> Configuration\n"
                     );

        $this->output->expects($this->at(1))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Name',
                             $configName
                         )
                     );

        $this->output->expects($this->at(2))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Database Driver',
                             'MongoDB'
                         )
                     );

        $this->output->expects($this->at(3))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Database Name',
                             $migrationsDatabaseName
                         )
                     );
        $this->output->expects($this->at(4))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Configuration Source',
                             'manually configured'
                         )
                     );
        $this->output->expects($this->at(5))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Version Collection Name',
                             $migrationsCollectionName
                         )
                     );
        $this->output->expects($this->at(6))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Migrations Namespace',
                             $migrationsNamespace
                         )
                     );
        $this->output->expects($this->at(7))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Migrations Directory',
                             $migrationsDirectory
                         )
                     );
        $this->output->expects($this->at(8))// current version formatted
                     ->method('writeln');
        $this->output->expects($this->at(9))// latest version formatted
                     ->method('writeln');
        $this->output->expects($this->at(10))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Executed Migrations',
                             $numExecutedMigrations
                         )
                     );
        $this->output->expects($this->at(11))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::<error>%s</error>',
                             'Executed Unavailable Migrations',
                             $numExecutedUnavailableMigrations
                         )
                     );
        $this->output->expects($this->at(12))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::%s',
                             'Available Migrations',
                             $numAvailableMigrations
                         )
                     );
        $this->output->expects($this->at(13))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '%s::<question>%s</question>',
                             'New Migrations',
                             $numNewMigrations
                         )
                     );
        $this->output->expects($this->at(14))
                     ->method('writeln')
                     ->with("\n <info>==</info> Available Migration Versions\n");

        $this->output->expects($this->at(39))
                     ->method('writeln')
                     ->with("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");

        $this->output->expects($this->at(40))
                     ->method('writeln')
                     ->with(
                         \sprintf(
                             '    <comment>>></comment> %s (<comment>%s</comment>)',
                             \DateTime::createFromFormat('YmdHis', $unavailableMigratedVersion)->format('Y-m-d H:i:s'),
                             $unavailableMigratedVersion
                         )
                     );

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class StatusCommandStub extends StatusCommand
{
    private $configuration;

    public function setMigrationConfiguration(ConfigurationInterface $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getMigrationConfiguration(InputInterface $input, OutputInterface $output): ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * Overwite complex string passed to OutputInterface::writeln
     * so we can set simple expectations on the value passed to this function.
     */
    protected function writeInfoLine(OutputInterface $output, $name, $value)
    {
        $output->writeln($name . '::' . $value);
    }
}

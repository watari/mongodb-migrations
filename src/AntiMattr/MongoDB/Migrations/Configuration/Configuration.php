<?php
declare(strict_types=1);

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException;
use AntiMattr\MongoDB\Migrations\Exception\DuplicateVersionException;
use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\MongoDB\Migrations\Version;
use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Connection;
use Doctrine\MongoDB\Database;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    const MIGRATION_DIRECTION_UP = 'up';
    const MIGRATION_DIRECTION_DOWN = 'down';

    const DEFAULT_PREFIX = 'app';

    /**
     * @var \Doctrine\MongoDB\Collection
     */
    private $collection;

    /**
     * @var \Doctrine\MongoDB\Connection
     */
    private $connection;

    /**
     * @var \Doctrine\MongoDB\Database
     */
    private $database;

    /**
     * The migration database name to track versions in.
     *
     * @var string
     */
    private $migrationsDatabaseName;

    /**
     * Flag for whether or not the migration collection has been created.
     *
     * @var bool
     */
    private $migrationCollectionCreated = false;

    /**
     * The migration collection name to track versions in.
     *
     * @var string
     */
    private $migrationsCollectionName = 'antimattr_migration_versions';

    /**
     * The path to a directory where new migration classes will be written.
     *
     * @var string
     */
    private $migrationsDirectory;

    /**
     * Namespace the migration classes live in.
     *
     * @var string
     */
    private $migrationsNamespace;

    /**
     * The path to a directory where mongo console scripts are.
     *
     * @var string
     */
    private $migrationsScriptDirectory;

    /**
     * Used by Console Commands and Output Writer.
     *
     * @var string
     */
    private $name;


    /**
     * With this value will be marked all migrations in database. This mark will allow to group migrations by their
     * origin.
     *
     * @var string
     */
    protected $prefix;

    /**
     * @var \AntiMattr\MongoDB\Migrations\Version[]
     */
    protected $migrations = [];

    /**
     * @var \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * @var string
     */
    private $file;

    /**
     * @param \Doctrine\MongoDB\Connection               $connection
     * @param \AntiMattr\MongoDB\Migrations\OutputWriter $outputWriter
     */
    public function __construct(Connection $connection, OutputWriter $outputWriter = null)
    {
        $this->connection = $connection;
        if (null === $outputWriter) {
            $outputWriter = new OutputWriter();
        }
        $this->outputWriter = $outputWriter;
    }

    /**
     * Returns a timestamp version as a formatted date.
     *
     * @param string $version
     *
     * @return string The formatted version
     */
    public static function formatVersion($version): string
    {
        return \sprintf('%s-%s-%s %s:%s:%s',
            \substr($version, 0, 4),
            \substr($version, 4, 2),
            \substr($version, 6, 2),
            \substr($version, 8, 2),
            \substr($version, 10, 2),
            \substr($version, 12, 2)
        );
    }

    /**
     * Returns an array of available migration version numbers.
     *
     * @return array
     */
    public function getAvailableVersions(): array
    {
        $availableVersions = [];
        foreach ($this->migrations as $migration) {
            $availableVersions[] = $migration->getVersion();
        }

        return $availableVersions;
    }

    /**
     * @return \Doctrine\MongoDB\Collection|null
     */
    public function getCollection(): ?Collection
    {
        if (isset($this->collection)) {
            return $this->collection;
        }

        $this->collection = $this->getDatabase()->selectCollection($this->migrationsCollectionName);

        return $this->collection;
    }

    /**
     * @return \Doctrine\MongoDB\Connection|null
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * @return \Doctrine\MongoDB\Database|null
     */
    public function getDatabase(): ?Database
    {
        if (isset($this->database)) {
            return $this->database;
        }

        $this->database = $this->connection->selectDatabase($this->migrationsDatabaseName);

        return $this->database;
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return Version[] $migrations
     */
    public function getMigrations(): array
    {
        return $this->migrations;
    }

    /**
     * @param string|null $databaseName
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsDatabaseName(?string $databaseName): ConfigurationInterface
    {
        $this->migrationsDatabaseName = $databaseName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMigrationsDatabaseName(): ?string
    {
        return $this->migrationsDatabaseName;
    }

    /**
     * @param string|null $collectionName
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsCollectionName(?string $collectionName): ConfigurationInterface
    {
        $this->migrationsCollectionName = $collectionName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMigrationsCollectionName(): ?string
    {
        return $this->migrationsCollectionName;
    }

    /**
     * @param string $migrationsDirectory
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsDirectory(string $migrationsDirectory): ConfigurationInterface
    {
        $this->migrationsDirectory = $migrationsDirectory;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMigrationsDirectory(): ?string
    {
        return $this->migrationsDirectory;
    }

    /**
     * Set the migrations namespace.
     *
     * @param string|null $migrationsNamespace The migrations namespace
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsNamespace(?string $migrationsNamespace): ConfigurationInterface
    {
        $this->migrationsNamespace = $migrationsNamespace;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMigrationsNamespace(): ?string
    {
        return $this->migrationsNamespace;
    }

    /**
     * @param string $scriptsDirectory
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsScriptDirectory(string $scriptsDirectory): ConfigurationInterface
    {
        $this->migrationsScriptDirectory = $scriptsDirectory;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMigrationsScriptDirectory(): ?string
    {
        return $this->migrationsScriptDirectory;
    }

    /**
     * @param string|null $file
     *
     * @return self
     */
    public function setFile(?string $file): ConfigurationInterface
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Returns all migrated versions from the versions collection, in an array.
     *
     * @return \AntiMattr\MongoDB\Migrations\Version[]
     */
    public function getMigratedVersions(): array
    {
        $this->createMigrationCollection();

        $cursor = $this->getCollection()->find(['prefix' => $this->prefix]);
        $versions = [];
        foreach ($cursor as $record) {
            $versions[] = $record['v'];
        }

        return $versions;
    }

    /**
     * Returns the time a migration occurred.
     *
     * @param string $version
     *
     * @return int
     *
     * @throws \AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException Throws exception if migration version
     *     does not exist
     * @throws \DomainException                                                If more than one version exists
     */
    public function getMigratedTimestamp(string $version): int
    {
        $this->createMigrationCollection();

        $cursor = $this->getCollection()->find(['v' => $version, 'prefix' => $this->prefix]);

        if (!$cursor->count()) {
            throw new UnknownVersionException($version);
        }

        if ($cursor->count() > 1) {
            throw new \DomainException(
                'Unexpected duplicate version records in the database'
            );
        }

        $returnVersion = $cursor->getNext();

        // Convert to normalised timestamp
        $ts = new Timestamp($returnVersion['t']);

        return $ts->getTimestamp();
    }

    /**
     * Return all migrated versions from versions collection that have migration files deleted.
     *
     * @return array
     */
    public function getUnavailableMigratedVersions(): array
    {
        return \array_diff($this->getMigratedVersions(), $this->getAvailableVersions());
    }

    /**
     * @param string|null $name
     *
     * @return self
     */
    public function setName(?string $name): ConfigurationInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return ($this->name) ?: 'Database Migrations';
    }

    /**
     * @return int
     */
    public function getNumberOfAvailableMigrations(): int
    {
        return \count($this->migrations);
    }

    /**
     * @return int
     */
    public function getNumberOfExecutedMigrations(): int
    {
        $this->createMigrationCollection();

        $cursor = $this->getCollection()->find(['prefix' => $this->prefix]);

        return $cursor->count();
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    public function getOutputWriter(): OutputWriter
    {
        return $this->outputWriter;
    }

    /**
     * Register a single migration version to be executed by a AbstractMigration
     * class.
     *
     * @param string $version The version of the migration in the format YYYYMMDDHHMMSS
     * @param string $class The migration class to execute for the version
     *
     * @return Version
     *
     * @throws \AntiMattr\MongoDB\Migrations\Exception\DuplicateVersionException
     */
    public function registerMigration(string $version, string $class): Version
    {
        $version = (string)$version;
        $class = (string)$class;
        if (isset($this->migrations[$version])) {
            $message = \sprintf(
                'Migration version %s already registered with class %s',
                $version,
                \get_class($this->migrations[$version])
            );
            throw new DuplicateVersionException($message);
        }
        $version = new Version($this, $this->prefix, $version, $class);
        $this->migrations[$version->getVersion()] = $version;
        \ksort($this->migrations);

        return $version;
    }

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     *
     * @return Version[]
     */
    public function registerMigrations(array $migrations): array
    {
        $versions = [];
        foreach ($migrations as $version => $class) {
            $versions[] = $this->registerMigration($version, $class);
        }

        return $versions;
    }

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path The root directory to where some migration classes live
     *
     * @return Version[] The array of migrations registered
     */
    public function registerMigrationsFromDirectory(string $path): array
    {
        $path = \realpath($path);
        $path = \rtrim($path, '/');
        $files = \glob($path . '/Version*.php');
        $versions = [];
        if (!empty($files)) {
            foreach ($files as $file) {
                require_once $file;
                $info = \pathinfo($file);
                $version = \substr($info['filename'], 7);
                $class = $this->migrationsNamespace . '\\' . $info['filename'];
                $versions[] = $this->registerMigration($version, $class);
            }
        }

        return $versions;
    }

    /**
     * Returns the Version instance for a given version in the format YYYYMMDDHHMMSS.
     *
     * @param string $version The version string in the format YYYYMMDDHHMMSS
     *
     * @return \AntiMattr\MongoDB\Migrations\Version
     *
     * @throws \AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException Throws exception if migration version
     *     does not exist
     */
    public function getVersion(string $version): Version
    {
        if (!isset($this->migrations[$version])) {
            throw new UnknownVersionException($version);
        }

        return $this->migrations[$version];
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     *
     * @return bool
     */
    public function hasVersion(string $version): bool
    {
        return isset($this->migrations[$version]);
    }

    /**
     * Check if a version has been migrated or not yet.
     *
     * @param \AntiMattr\MongoDB\Migrations\Version $version
     *
     * @return bool
     */
    public function hasVersionMigrated(Version $version): bool
    {
        $this->createMigrationCollection();

        $record = $this->getCollection()->findOne(['v' => $version->getVersion(), 'prefix' => $this->prefix]);

        return null !== $record;
    }

    /**
     * @return string
     */
    public function getCurrentVersion(): string
    {
        $this->createMigrationCollection();

        $migratedVersions = [];
        if (!empty($this->migrations)) {
            foreach ($this->migrations as $migration) {
                $migratedVersions[] = $migration->getVersion();
            }
        }

        $cursor = $this->getCollection()
                       ->find(['v' => ['$in' => $migratedVersions], 'prefix' => $this->prefix])
                       ->sort(['v' => -1])
                       ->limit(1);

        if (0 === $cursor->count()) {
            return '0';
        }

        $version = $cursor->getNext();

        return $version['v'];
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string The version string in the format YYYYMMDDHHMMSS
     */
    public function getLatestVersion(): string
    {
        $versions = \array_keys($this->migrations);
        $latest = \end($versions);

        return false !== $latest ? (string)$latest : '0';
    }

    /**
     * Create the migration collection to track migrations with.
     *
     * @return bool Whether or not the collection was created
     */
    public function createMigrationCollection(): bool
    {
        $this->validate();

        if (!$this->migrationCollectionCreated) {
            $collection = $this->getCollection();
            $collection->ensureIndex(['v' => -1, 'prefix' => -1], ['name' => 'version', 'unique' => true]);
            $this->migrationCollectionCreated = true;
        }

        return true;
    }

    /**
     * Returns the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction The direction we are migrating
     * @param string $to The version to migrate to
     *
     * @return Version[] $migrations   The array of migrations we can execute
     */
    public function getMigrationsToExecute(string $direction, string $to): array
    {
        if (self::MIGRATION_DIRECTION_DOWN === $direction) {
            if (\count($this->migrations)) {
                $allVersions = \array_reverse(\array_keys($this->migrations));
                $classes = \array_reverse(\array_values($this->migrations));
                $allVersions = \array_combine($allVersions, $classes);
            } else {
                $allVersions = [];
            }
        } else {
            $allVersions = $this->migrations;
        }
        $versions = [];
        $migrated = $this->getMigratedVersions();
        foreach ($allVersions as $version) {
            if ($this->shouldExecuteMigration($direction, $version, $to, $migrated)) {
                $versions[$version->getVersion()] = $version;
            }
        }

        return $versions;
    }

    /**
     * Validation that this instance has all the required properties configured.
     *
     * @throws \AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function validate(): void
    {
        if (empty($this->migrationsDatabaseName)) {
            $message = 'Migrations Database Name must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
        if (empty($this->migrationsNamespace)) {
            $message = 'Migrations namespace must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
        if (empty($this->migrationsDirectory)) {
            $message = 'Migrations directory must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *
     * @return ConfigurationInterface
     */
    public function setPrefix(string $prefix): ConfigurationInterface
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return array
     */
    public function getDetailsMap(): array
    {
        // Executed migration count
        $executedMigrations = $this->getMigratedVersions();
        $numExecutedMigrations = \count($executedMigrations);

        // Available migration count
        $availableMigrations = $this->getAvailableVersions();
        $numAvailableMigrations = \count($availableMigrations);

        // Executed Unavailable migration count
        $numExecutedUnavailableMigrations = \count($this->getUnavailableMigratedVersions());

        // New migration count
        $numNewMigrations = $numAvailableMigrations - ($numExecutedMigrations - $numExecutedUnavailableMigrations);

        return [
            'name' => $this->getName(),
            'database_driver' => 'MongoDB',
            'migrations_database_name' => $this->getMigrationsDatabaseName(),
            'migrations_collection_name' => $this->getMigrationsCollectionName(),
            'migrations_namespace' => $this->getMigrationsNamespace(),
            'migrations_directory' => $this->getMigrationsDirectory(),
            'current_version' => $this->getCurrentVersion(),
            'latest_version' => $this->getLatestVersion(),
            'num_executed_migrations' => $numExecutedMigrations,
            'num_executed_unavailable_migrations' => $numExecutedUnavailableMigrations,
            'num_available_migrations' => $numAvailableMigrations,
            'num_new_migrations' => $numNewMigrations,
        ];
    }

    /**
     * Check if we should execute a migration for a given direction and target
     * migration version.
     *
     * @param string  $direction The direction we are migrating
     * @param Version $version The Version instance to check
     * @param string  $to The version we are migrating to
     * @param array   $migrated Migrated versions array
     *
     * @return bool
     */
    private function shouldExecuteMigration(string $direction, Version $version, string $to, array $migrated): bool
    {
        switch ($direction) {
            case self::MIGRATION_DIRECTION_DOWN:
                if (!\in_array($version->getVersion(), $migrated)) {
                    return false;
                }

                return $version->getVersion() > $to;

            case self::MIGRATION_DIRECTION_UP:
                if (\in_array($version->getVersion(), $migrated)) {
                    return false;
                }

                return $version->getVersion() <= $to;

            default:
                throw new \LogicException("Specified direction {$direction} is not supported.");
        }
    }
}

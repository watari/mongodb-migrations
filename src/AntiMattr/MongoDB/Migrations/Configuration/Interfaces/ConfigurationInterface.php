<?php
declare(strict_types=1);


namespace AntiMattr\MongoDB\Migrations\Configuration\Interfaces;

use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\MongoDB\Migrations\Version;
use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Connection;
use Doctrine\MongoDB\Database;

/**
 * Interface ConfigurationInterface
 * @package AntiMattr\MongoDB\Migrations\Configuration\Interfaces
 * @author Watari <watari.mailbox@gmail.com>
 */
interface ConfigurationInterface
{

    /**
     * Returns a timestamp version as a formatted date.
     *
     * @param string $version
     *
     * @return string The formatted version
     */
    public static function formatVersion($version): string;

    /**
     * Returns an array of available migration version numbers.
     *
     * @return array
     */
    public function getAvailableVersions(): array;

    /**
     * @return \Doctrine\MongoDB\Collection|null
     */
    public function getCollection(): ?Collection;

    /**
     * @return \Doctrine\MongoDB\Connection|null
     */
    public function getConnection(): ?Connection;

    /**
     * @return \Doctrine\MongoDB\Database|null
     */
    public function getDatabase(): ?Database;

    /**
     * Get the array of registered migration versions.
     *
     * @return Version[] $migrations
     */
    public function getMigrations(): array;

    /**
     * @param string|null $databaseName
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsDatabaseName(?string $databaseName): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getMigrationsDatabaseName(): ?string;

    /**
     * @param string|null $collectionName
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsCollectionName(?string $collectionName): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getMigrationsCollectionName(): ?string;

    /**
     * @param string $migrationsDirectory
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsDirectory(string $migrationsDirectory): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getMigrationsDirectory(): ?string;

    /**
     * Set the migrations namespace.
     *
     * @param string|null $migrationsNamespace The migrations namespace
     *
     * @return ConfigurationInterface
     */
    public function setMigrationsNamespace(?string $migrationsNamespace): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getMigrationsNamespace(): ?string;

    /**
     * @param string $scriptsDirectory
     *
     * @return self
     */
    public function setMigrationsScriptDirectory(string $scriptsDirectory): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getMigrationsScriptDirectory(): ?string;

    /**
     * @param string|null $file
     *
     * @return self
     */
    public function setFile(?string $file): ConfigurationInterface;

    /**
     * @return string|null
     */
    public function getFile(): ?string;

    /**
     * Returns all migrated versions from the versions collection, in an array.
     *
     * @return \AntiMattr\MongoDB\Migrations\Version[]
     */
    public function getMigratedVersions(): array;

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
    public function getMigratedTimestamp(string $version): int;

    /**
     * Return all migrated versions from versions collection that have migration files deleted.
     *
     * @return array
     */
    public function getUnavailableMigratedVersions(): array;

    /**
     * @param string|null $name
     *
     * @return self
     */
    public function setName(?string $name): ConfigurationInterface;

    /**
     * @return string $name
     */
    public function getName(): string;

    /**
     * @return int
     */
    public function getNumberOfAvailableMigrations(): int;

    /**
     * @return int
     */
    public function getNumberOfExecutedMigrations(): int;

    /**
     * @return \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    public function getOutputWriter(): OutputWriter;

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
    public function registerMigration(string $version, string $class): Version;

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     *
     * @return Version[]
     */
    public function registerMigrations(array $migrations): array;

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path The root directory to where some migration classes live
     *
     * @return Version[] The array of migrations registered
     */
    public function registerMigrationsFromDirectory(string $path): array;

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
    public function getVersion(string $version): Version;

    /**
     * Check if a version exists.
     *
     * @param string $version
     *
     * @return bool
     */
    public function hasVersion(string $version): bool;

    /**
     * Check if a version has been migrated or not yet.
     *
     * @param \AntiMattr\MongoDB\Migrations\Version $version
     *
     * @return bool
     */
    public function hasVersionMigrated(Version $version): bool;

    /**
     * @return string
     */
    public function getCurrentVersion(): string;

    /**
     * Returns the latest available migration version.
     *
     * @return string The version string in the format YYYYMMDDHHMMSS
     */
    public function getLatestVersion(): string;

    /**
     * Create the migration collection to track migrations with.
     *
     * @return bool Whether or not the collection was created
     */
    public function createMigrationCollection(): bool;

    /**
     * Returns the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction The direction we are migrating
     * @param string $to The version to migrate to
     *
     * @return Version[] $migrations   The array of migrations we can execute
     */
    public function getMigrationsToExecute(string $direction, string $to): array;

    /**
     * Validation that this instance has all the required properties configured.
     *
     * @throws \AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function validate(): void;

    /**
     * @return array
     */
    public function getDetailsMap(): array;
}

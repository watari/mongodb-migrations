<?php
declare(strict_types=1);


namespace AntiMattr\MongoDB\Migrations\Configuration\Interfaces;

use AntiMattr\MongoDB\Migrations\OutputWriter;
use Doctrine\MongoDB\Connection;

/**
 * Interface ConfigurationBuilderInterface
 * @package AntiMattr\MongoDB\Migrations\Configuration\Interfaces
 * @author Watari <watari.mailbox@gmail.com>
 */
interface ConfigurationBuilderInterface
{

    /**
     * @return ConfigurationBuilderInterface
     */
    public static function create(): ConfigurationBuilderInterface;

    /**
     * @param Connection $connection
     *
     * @return self
     */
    public function setConnection(Connection $connection): self;

    /**
     * @param OutputWriter $outputWriter
     *
     * @return self
     */
    public function setOutputWriter(OutputWriter $outputWriter): self;

    /**
     * @param string|null $configFile
     *
     * @return self
     */
    public function setOnDiskConfiguration(?string $configFile = null): self;

    /**
     * @return ConfigurationInterface
     */
    public function build(): ConfigurationInterface;
}

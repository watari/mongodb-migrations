<?php
declare(strict_types = 1);

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author @author Watari <watari.mailbox@gmail.com>
 */
class BCFixCommand extends AbstractCommand
{
    public const NAME = 'mongodb:migrations:bc-fix';

    protected function configure(): void
    {
        $this
            ->setName($this->getName())
            ->setDescription('Add to existing migrations with missing prefix default app prefix.');

        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface
     *
     * @throws \InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $collection = $configuration->getCollection();
        $collection->findAndUpdate(
            ['prefix' => ['$exists' => false]],
            ['$set' => ['prefix' => Configuration::DEFAULT_PREFIX]]
        );
        $collection->deleteIndex(['v' => -1]);
        $collection->ensureIndex(['v' => -1, 'prefix' => -1], ['name' => 'version', 'unique' => true]);
    }

    public function getName()
    {
        return self::NAME;
    }
}

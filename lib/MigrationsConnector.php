<?php

namespace Maximaster;

use Arrilot\BitrixMigrations\Commands\MakeCommand;
use Arrilot\BitrixMigrations\Commands\InstallCommand;
use Arrilot\BitrixMigrations\Commands\MigrateCommand;
use Arrilot\BitrixMigrations\Commands\RollbackCommand;
use Arrilot\BitrixMigrations\Commands\TemplatesCommand;
use Arrilot\BitrixMigrations\Commands\StatusCommand;
use Arrilot\BitrixMigrations\Migrator;
use Maximaster\Extend\Arrilot\BitrixMigrations\Storages\BitrixDatabaseStorage;
use Arrilot\BitrixMigrations\TemplatesCollection;


class MigrationsConnector {

    public function getConfig()
    {
        return array(
            'table' => 'maximaster_db_migrations',
            'dir' => './migrations',
        );
    }

    /**
     * @return \Arrilot\BitrixMigrations\Commands\AbstractCommand[]
     */
    public function getCliCommandList()
    {
        $config = $this->getConfig();

        $database = new BitrixDatabaseStorage($config['table']);
        $templates = new TemplatesCollection();
        $templates->registerBasicTemplates();

        $migrator = new Migrator($config, $templates, $database);

        $migrationCommands = array(
            new MakeCommand($migrator),
            new InstallCommand($config['table'], $database),
            new MigrateCommand($migrator),
            new RollbackCommand($migrator),
            new TemplatesCommand($templates),
            new StatusCommand($migrator),
        );

        foreach ($migrationCommands as &$command) {
            /** @var \Arrilot\BitrixMigrations\Commands\AbstractCommand $command */
            $commandName = $command->getName();
            $command->setName('migration:' . $commandName);
        }

        return $migrationCommands;
    }
}
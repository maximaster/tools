<?php

namespace Maximaster\Tools\Migrations;

use Arrilot\BitrixMigrations\Commands\MakeCommand;
use Arrilot\BitrixMigrations\Commands\InstallCommand;
use Arrilot\BitrixMigrations\Commands\MigrateCommand;
use Arrilot\BitrixMigrations\Commands\RollbackCommand;
use Arrilot\BitrixMigrations\Commands\TemplatesCommand;
use Arrilot\BitrixMigrations\Commands\StatusCommand;
use Arrilot\BitrixMigrations\Migrator;
use Maximaster\Extend\Arrilot\BitrixMigrations\Storages\BitrixDatabaseStorage;
use Arrilot\BitrixMigrations\TemplatesCollection;

/**
 * Класс, который позволяет прицепить команды модуля миграции к console-jedi
 * @package Maximaster
 */
class MigrationsAdapter
{
    private $migrator = null;
    private $storage = null;
    private $templates = null;

    public function __construct()
    {
        $config = $this->getConfig();

        $this->storage = new BitrixDatabaseStorage($config[ 'table' ]);
        $this->templates = new TemplatesCollection();
        $this->templates->registerBasicTemplates();

        $this->migrator = new Migrator($config, $this->templates, $this->storage);
    }

    /**
     * Получает массив с конфигурацией модуля
     * table - название таблицы в БД, которая хранит миграции
     * dir - название директории в корне проекта, которая содержит файлы миграций
     * @return array
     */
    public function getConfig()
    {
        return array(
            'table' => 'maximaster_db_migrations',
            'dir' => './migrations',
        );
    }

    /**
     * Получает перечень всех команд
     * @return \Arrilot\BitrixMigrations\Commands\AbstractCommand[]
     */
    public function getCommands()
    {
        $migrationCommands = array_merge($this->getDatabaseCommands(), $this->getFileCommands());
        return $migrationCommands;
    }

    /**
     * Добавляет пространство имен для команд
     * @param \Arrilot\BitrixMigrations\Commands\AbstractCommand[] $commands
     * @return array
     */
    private function addNamespaceToCommands(array $commands)
    {
        foreach ($commands as &$command) {
            $commandName = $command->getName();
            $command->setName('migration:' . $commandName);
        }

        return $commands;
    }

    /**
     * Получает список команд, для выполнения которых требуется наличие подключения к БД
     * @return \Arrilot\BitrixMigrations\Commands\AbstractCommand[]
     */
    public function getDatabaseCommands()
    {
        $config = $this->getConfig();
        $commands = array(
            new InstallCommand($config[ 'table' ], $this->storage),
            new MigrateCommand($this->migrator),
            new RollbackCommand($this->migrator),
            new StatusCommand($this->migrator),
        );

        return $this->addNamespaceToCommands($commands);
    }

    /**
     * Получает список команд, для выполнения которых НЕ требуется наличие подключения к БД
     * @return \Arrilot\BitrixMigrations\Commands\AbstractCommand[]
     */
    public function getFileCommands()
    {
        $commands = array(
            new MakeCommand($this->migrator),
            new TemplatesCommand($this->templates),
        );

        return $this->addNamespaceToCommands($commands);
    }
}
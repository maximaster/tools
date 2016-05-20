<?php

namespace Maximaster\Extend\Arrilot\BitrixMigrations\Storages;

use Bitrix\Main\Application;

/**
 * Класс-расширение, которое позволяет организовать работу с миграциями БД с помощью связки между
 * arrilot/bitrix-migrations и notamedia/console-jedi
 *
 * @package Maximaster\Extend\Arrilot\BitrixMigrations\Storages
 */
class BitrixDatabaseStorage extends \Arrilot\BitrixMigrations\Storages\BitrixDatabaseStorage
{
    /**
     * Добавлен единственный метод, который берет подключение БД из приложения, если приложение уже инициализировано
     * Вызов этого метода добавлен во все родительские методы
     */
    private function initializeDatabaseConnection()
    {
        if (!$this->db && class_exists(Application::class)) {
            global $DB;
            $this->db = $DB;
        }
    }

    public function __construct($table)
    {
        parent::__construct($table);
        $this->initializeDatabaseConnection();
    }

    public function checkMigrationTableExistence()
    {
        $this->initializeDatabaseConnection();
        return parent::checkMigrationTableExistence();
    }

    public function createMigrationTable()
    {
        $this->initializeDatabaseConnection();
        parent::createMigrationTable();
    }

    public function getRanMigrations()
    {
        $this->initializeDatabaseConnection();
        return parent::getRanMigrations();
    }

    public function logSuccessfulMigration($name)
    {
        $this->initializeDatabaseConnection();
        parent::logSuccessfulMigration($name);
    }

    public function removeSuccessfulMigrationFromLog($name)
    {
        $this->initializeDatabaseConnection();
        parent::removeSuccessfulMigrationFromLog($name);
    }
}
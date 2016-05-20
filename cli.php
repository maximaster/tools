<?php

/**
 * Регистрируем все команды миграций arrilot/bitrix-migrations
 */
$migrationsConnector = new \Maximaster\MigrationsConnector();
$migrationsCommands = $migrationsConnector->getCliCommandList();

$commands = $migrationsCommands;

$config = array(
    'commands' => $commands
);

return $config;
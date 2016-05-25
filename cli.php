<?php

/**
 * Регистрируем все команды миграций arrilot/bitrix-migrations
 */
$migrationsConnector = new \Maximaster\Tools\Migrations\MigrationsAdapter();
$migrationsCommands = $migrationsConnector->getCommands();

/**
 * Регистрируем команды для twig
 */
$twigCommands = array(
    new \Maximaster\Tools\Twig\Command\ClearCacheCommand()
);
$commands = array_merge($twigCommands, $migrationsCommands);

$config = array(
    'commands' => $commands
);

return $config;
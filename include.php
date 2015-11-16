<?php

use Maximaster\Tools\Psr4Autoloader;

require_once 'lib/Psr4Autoloader.php';

$loader = Psr4Autoloader::getInstance();
$loader->addNamespace('\\Maximaster\\Tools', __DIR__ . '/lib/');
$loader->register();
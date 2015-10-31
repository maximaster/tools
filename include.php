<?php

use Mx\Tools\Psr4Autoloader;

require_once 'lib/Psr4Autoloader.php';

$loader = Psr4Autoloader::getInstance();
$loader->addNamespace('\\Mx\\Tools', __DIR__ . '/lib/');
$loader->register();
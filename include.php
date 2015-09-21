<?php

use Mx\Tools\Psr4Autoloader;

require_once 'lib/Psr4Autoloader.php';

$loader = new Psr4Autoloader();
$loader->addNamespace('\\Mx\\Tools', __DIR__ . '/lib/');
$loader->register();
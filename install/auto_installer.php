<?php

use Bitrix\Main\ModuleManager;

if (!ModuleManager::isModuleInstalled('maximaster.tools')) {
    require_once( 'index.php' );
    $moduleInstaller = new maximaster_tools();
    $moduleInstaller->DoInstall();
}
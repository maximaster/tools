<?
class mx_tools extends \CModule
{
    const MODULE_ID = 'mx.tools';

    function __construct()
    {
        $arModuleVersion = array();
        include( dirname(__FILE__) . "/version.php" );
        $this->MODULE_ID = self::MODULE_ID;
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = 'Инструменты Maximaster';
        $this->MODULE_DESCRIPTION = '';

        $this->PARTNER_NAME = 'ООО Максимастер';
        $this->PARTNER_URI = 'http://www.maximaster.ru';
    }

    function InstallDB($arParams = array())
    {
        return true;
    }

    function UnInstallDB($arParams = array())
    {
       return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }

    function InstallFiles($arParams = array())
    {
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    function DoInstall()
    {
        RegisterModule(self::MODULE_ID);
    }

    function DoUninstall()
    {
        UnRegisterModule(self::MODULE_ID);
    }
}

<?php

namespace Mx\Tools\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;

class IblockStructure
{
    public static function iblock($primary)
    {
        $cache = new \CPHPCache();

        $cacheId = md5($primary);
        if ($cache->InitCache(86400, $cacheId, str_replace(array('\\', ':'), '/', __METHOD__)))
        {
            $iblock = $cache->GetVars();
        }
        else
        {
            $field = is_numeric($primary) ? 'ID' : 'CODE';
            $db = IblockTable::query()->addFilter($field, $primary)->setSelect(array('*'))->exec();
            if ($db->getSelectedRowsCount() == 0)
            {
                $cache->AbortDataCache();
                throw new ArgumentException('Указан идентификатор несуществующего инфоблока');
            }
            elseif ($db->getSelectedRowsCount() > 1)
            {
                $cache->AbortDataCache();
                throw new ArgumentException("Существует {$db->getSelectedRowsCount()} инфоблока(ов) с {$field} = {$primary}");
            }

            $iblock = $db->fetch();


            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($iblock);
            }
        }

        return $iblock;

    }

    public static function properties($primary)
    {
        $cache = new \CPHPCache();

        $cacheId = md5($primary);
        if ($cache->InitCache(86400, $cacheId, str_replace(array('\\', ':'), '/', __METHOD__)))
        {
            $props = $cache->GetVars();
        }
        else
        {
            $field = is_numeric($primary) ? 'IBLOCK_ID' : 'IBLOCK.CODE';

            $db = PropertyTable::query()->addFilter($field, $primary)->addSelect('*')->exec();
            $props = array();
            while ($prop = $db->fetch())
            {
                $code = $prop['CODE'];
                if (isset($props[ $code ]))
                {
                    throw new \LogicException("В инфоблокe {$primary} свойство {$code} используется дважды");
                }

                if (strlen($code) === 0)
                {
                    throw new \LogicException("В инфоблоке {$primary} для свойства {$prop['NAME']} не задан символьный код");
                }

                $props[ $code ] = $prop;
            }

            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($props);
            }
        }


        return $props;
    }

    public static function full($primary)
    {
        return array(
            'iblock' => self::iblock($primary),
            'properties' => self::properties($primary)
        );
    }
}
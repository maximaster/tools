<?php

namespace Mx\Tools\Orm\Iblock;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity;

abstract class ElementTable extends \Bitrix\Iblock\ElementTable
{
    protected static $iblockCode;
    protected static $iblockId;

    public static function getMap()
    {
        $map = parent::getMap();

        $meta = self::getIblockData(static::$iblockCode);
        $iblock = $meta['iblock'];
        $props = $meta['properties'];

        $oldProps = $iblock['VERSION'] == 1;
        if (empty($props)) return $map;

        if (!$oldProps)
        {
            $singleProp = ElementPropSingleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
            $multipleProp = ElementPropMultipleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
        }
        else
        {
            $singleProp = ElementPropertyTable::getEntity()->getDataClass();
        }

        $map['PROPERTY_ENTITY'] = new ReferenceField(
            'PROPERTY_ENTITY',
            $singleProp,
            array(
                'ref.IBLOCK_ELEMENT_ID' => 'this.ID'
            ),
            array('join_type' => 'INNER')
        );

        foreach ($props as $code => $prop)
        {
            $id = $prop['ID'];
            $isMultiple = $prop['MULTIPLE'] == 'Y';

            if ($oldProps)
            {
                $column = 'VALUE';
                switch ($prop['PROPERTY_TYPE']) {
                    case 'N':
                    case 'E':
                    case 'G':
                        $column = 'VALUE_NUM';
                        break;
                    case 'L':
                    case 'S':
                    default:
                        $column = 'VALUE';
                        break;
                }

                $mapItem = new ExpressionField(
                    $code,
                    '%s',
                    'PROPERTY_ENTITY.' . $column
                );
                $map[ $code . '_DESCRIPTION' ] = new ExpressionField(
                    $code . '_DESCRIPTION' ,
                    '%s',
                    'PROPERTY_ENTITY.DESCRIPTION'
                );
            }
            else
            {
                if ($isMultiple)
                {

                }
                else
                {
                    $mapItem = new ExpressionField(
                        $code,
                        '%s',
                        'PROPERTY_ENTITY.' . $code
                    );
                }

            }

            $map[ $code . '_DESCRIPTION' ] = new ExpressionField(
                $code . '_DESCRIPTION' ,
                '%s',
                'PROPERTY_ENTITY.' . $code . '_DESCRIPTION'
            );

            $map[ $code ] = $mapItem;
        }

        return $map;
    }

    private static function getProperties($iblockId)
    {
        $dbProps = PropertyTable::getList(array(
            'filter' => array('IBLOCK_ID' => $iblockId)
        ));
        $props = array();
        while ($prop = $dbProps->fetch())
        {
            $code = $prop['CODE'];
            if (isset($props[ $code ]))
            {
                throw new \LogicException("В инфоблоке {$iblockId} свойство {$code} используется дважды");
            }

            if (strlen($code) === 0)
            {
                throw new \LogicException("В инфоблоке {$iblockId} для свойства {$prop['NAME']} не задан символьный код");
            }

            $props[ $code ] = $prop;
        }

        return $props;

    }

    private static function getIblock($code)
    {
        return IblockTable::query()->addFilter('CODE', $code)->setSelect(array('*'))->exec()->fetch();
    }

    public static function getIblockData($iblockCode)
    {
        $cache = new \CPHPCache();

        $iblockData = array();
        $cacheId = md5('kJw0eko1plm,kasdedasfpo;jl3m' . $iblockCode);
        if ($cache->InitCache(86400, $cacheId, str_replace(array('\\', ':'), '/', __METHOD__)))
        {
            $iblockData = $cache->GetVars();
        }
        else
        {
            $iblock = self::getIblock($iblockCode);
            if (!$iblock)
            {
                $cache->AbortDataCache();
                throw new ArgumentException('Указан код несуществующего инфоблока');
            }
            $iblockData = array(
                'iblock' => $iblock,
                'properties' => self::getProperties($iblock['ID']),
            );

            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($iblockData);
            }
        }

        return $iblockData;

    }

    /**
     * @param $code
     * @return mixed
     */
    public static function getInstance($code)
    {
        $meta = self::getIblockData($code);
        self::$iblockCode = $meta['iblock']['CODE'];
        self::$iblockId = $meta['iblock']['ID'];
        return self::compileEntity();
    }

    private static function compileEntity()
    {
        $class = Base::snake2camel(static::$iblockCode) . 'Table';

        if (!class_exists($class))
        {
            $eval = 'class '.$class.' extends ' . __CLASS__ . ' {}';
            eval($eval);
        }

        return new $class;
    }

    public static function query()
    {
        return parent::query()->addFilter('IBLOCK_ID', static::$iblockId);
    }
}
<?php

namespace Mx\Tools\Orm\Iblock;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Mx\Tools\Helpers\IblockStructure;
use Mx\Tools\Interfaces\IblockElementTableInterface;
use Mx\Tools\Orm\Query;


class ElementTable extends \Bitrix\Iblock\ElementTable implements IblockElementTableInterface
{
    public static function getIblockId()
    {
        return null;
    }

    public static function getMap()
    {
        if (static::getIblockId() === null) return parent::getMap();

        $map = parent::getMap();

        foreach (self::getAdditionalMap() as $mapItem)
        {
            $map[] = $mapItem;
        }

        return $map;
    }

    /**
     * Получает список дополнительных полей, которые нужно добавить в сущность
     *
     * @param null|int|string $iblockId Идентификатор инфоблока или код инфоблока или null. В случае null будет выполнена
     * попытка получить идентификатор инфоблока из сущности
     * @return array Список дополнительных полей для сущности
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getAdditionalMap($iblockId = null)
    {
        $map = array();
        $iblockId = $iblockId === null ? static::getIblockId() : $iblockId;

        $meta = IblockStructure::full($iblockId);

        $iblock = $meta['iblock'];
        $props = $meta['properties'];

        if (empty($props)) return $map;

        $oldProps = $iblock['VERSION'] == 1;

        if (!$oldProps)
        {
            $singleProp = ElementPropSingleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
            //$multipleProp = ElementPropMultipleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
        }
        else
        {
            $singleProp = ElementPropertyTable::getEntity()->getDataClass();
        }

        foreach ($props as $code => $prop)
        {
            if (is_numeric($code)) continue;

            $isMultiple = $prop['MULTIPLE'] == 'Y';
            if ($isMultiple) continue;

            $valueKey = "PROPERTY_{$code}_VALUE";
            $descriptionKey = "PROPERTY_{$code}_DESCRIPTION";

            $propertyEntity = null;

            if ($oldProps)
            {
                switch ($prop['PROPERTY_TYPE'])
                {
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

                $propertyEntity = new Entity\ReferenceField(
                    'PROPERTY_' . $code,
                    $singleProp,
                    array(
                        '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                        '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $prop['ID'])
                    ),
                    array('join_type' => 'LEFT')
                );

                $map[ $valueKey ] = new Entity\ExpressionField(
                    $valueKey,
                    '%s',
                    "PROPERTY_{$code}.{$column}"
                );

                if ($prop['WITH_DESCRIPTION'] == 'Y')
                {
                    $map[ $descriptionKey ] = new Entity\ExpressionField(
                        $descriptionKey,
                        '%s',
                        "PROPERTY_{$code}.DESCRIPTION"
                    );
                }
            }
            else
            {
                $propertyEntity = new Entity\ReferenceField(
                    'PROPERTY_' . $code,
                    $singleProp,
                    array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                    array('join_type' => 'LEFT')
                );

                $map[ $valueKey ] = new Entity\ExpressionField(
                    $valueKey,
                    '%s',
                    "PROPERTY_{$code}.{$prop['CODE']}"
                );

                if ($prop['WITH_DESCRIPTION'] == 'Y')
                {
                    $map[ $descriptionKey ] = new Entity\ExpressionField(
                        $descriptionKey,
                        '%s',
                        "PROPERTY_{$code}.{$prop['CODE']}_DESCRIPTION"
                    );
                }
            }

            if ($propertyEntity !== null)
            {
                $map[ $propertyEntity->getName() ] = $propertyEntity;
            }
        }

        return $map;
    }

    public static function query()
    {
        return new Query(static::getEntity());
    }
}
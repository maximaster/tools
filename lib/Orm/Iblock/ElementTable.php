<?php

namespace Mx\Tools\Orm\Iblock;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Mx\Tools\Helpers\IblockStructure;
use Mx\Tools\Interfaces\IblockElementTableInterface;
use Mx\Tools\Orm\Query;


class ElementTable extends \Bitrix\Iblock\ElementTable implements IblockElementTableInterface
{

    private static $concatSeparator = '|<-separator->|';

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

        $isOldProps = $iblock['VERSION'] == 1;

        $singlePropTableLinked = false;
        $singlePropsEntityName = "PROPERTY_TABLE_IBLOCK_{$iblock['ID']}";

        if (!$isOldProps)
        {
            $singleProp = ElementPropSingleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
            $multipleProp = ElementPropMultipleTable::getInstance($iblock['CODE'])->getEntity()->getDataClass();
        }
        else
        {
            $singleProp = ElementPropertyTable::getEntity()->getDataClass();
        }

        foreach ($props as $code => $prop)
        {
            if (is_numeric($code)) continue;

            $isMultiple     = $prop['MULTIPLE'] == 'Y';
            $useDescription = $prop['WITH_DESCRIPTION'] == 'Y';
            $isNewMultiple  = $isMultiple && !$isOldProps;
            $valueKey       = "PROPERTY_{$code}_VALUE";
            $descriptionKey = "PROPERTY_{$code}_DESCRIPTION";
            $concatSubquery = "GROUP_CONCAT(%s SEPARATOR '" .  static::$concatSeparator . "')";
            $propertyEntity = null;
            $valueColumn    = 'VALUE';

            /*switch ($prop['PROPERTY_TYPE'])
            {
                case 'N': case 'E': case 'G':   $valueColumn = 'VALUE_NUM';  break;
                case 'L': case 'S': default:    $valueColumn = 'VALUE';      break;
            }*/

            /**
             * Для всех свойств, кроме одиночных 2.0
             */
            if ($isOldProps || $isMultiple)
            {
                /**
                 * Для инфоблоков 1.0 нужно цеплять на каждое значение по одной сущности
                 */
                $propertyEntity = new Entity\ReferenceField(
                    'PROPERTY_' . $code,
                    $isNewMultiple ? $multipleProp : $singleProp,
                    array(
                        '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                        '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $prop['ID'])
                    ),
                    array('join_type' => 'LEFT')
                );

                /**
                 * Делаем быстрый доступ для значения свойства
                 */
                $e = new Entity\ExpressionField(
                    $valueKey,
                    $isMultiple ? $concatSubquery : '%s',
                    "PROPERTY_{$code}.{$valueColumn}"
                );
                if ($isMultiple) $e->addFetchDataModifier(array(__CLASS__, 'multiValuesDataModifier'));
                $map[ $valueKey ] = $e;

                /**
                 * И для его описания, если оно есть
                 */
                if ($useDescription)
                {
                    $e = new Entity\ExpressionField(
                        $descriptionKey,
                        $isMultiple ? $concatSubquery : '%s',
                        "PROPERTY_{$code}.DESCRIPTION"
                    );
                    if ($isMultiple) $e->addFetchDataModifier(array(__CLASS__, 'multiValuesDataModifier'));
                    $map[ $descriptionKey ] = $e;
                }
            }
            else
            {
                /**
                 * Для не множественных свойств 2.0 цепляем только одну сущность
                 */
                if (!$singlePropTableLinked)
                {
                    $propertyEntity = new Entity\ReferenceField(
                        $singlePropsEntityName,
                        $singleProp,
                        array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                        array('join_type' => 'LEFT')
                    );

                    $singlePropTableLinked = true;
                }
                else
                {
                    $map[ $valueKey ] = new Entity\ExpressionField(
                        $valueKey,
                        '%s',
                        "{$singlePropsEntityName}.{$prop['CODE']}"
                    );

                    if ($useDescription)
                    {
                        $map[ $descriptionKey ] = new Entity\ExpressionField(
                            $descriptionKey,
                            '%s',
                            "{$singlePropsEntityName}.{$prop['CODE']}_DESCRIPTION"
                        );
                    }
                }
            }

            if ($propertyEntity !== null)
            {
                $map[ $propertyEntity->getName() ] = $propertyEntity;
            }
        }

        /**
         * Добавим DATAIL_PAGE_URL
         */
        $e = new Entity\ExpressionField('DETAIL_PAGE_URL', '%s', 'IBLOCK.DETAIL_PAGE_URL');
        $e->addFetchDataModifier(function($value, $query, $entry, $fieldName)
        {
            $search = array();
            $replace = array();
            foreach ($entry as $key => $val)
            {
                $search[] = "#{$key}#";
                $replace[] = $val;
            }
            return str_replace($search, $replace, $value);
        });
        $map['DETAIL_PAGE_URL'] = $e;

        return $map;
    }

    /**
     * Модификатор данных для множественных свойств. Разрезает строку со сгруппированным значением множественных свойств
     *
     * @param $value
     * @param $query
     * @param $entry
     * @param $fieldName
     * @return array
     */
    public static function multiValuesDataModifier($value, $query, $entry, $fieldName)
    {
        if (
            trim($value) == static::$concatSeparator
            || strpos($value, static::$concatSeparator) === false

        ) return array();

        return explode(static::$concatSeparator, $value);
    }

    /**
     * Подмена встроенного запроса на модифицированный
     *
     * @return Query
     */
    public static function query()
    {
        return new Query(static::getEntity());
    }

    public static function add(array $data)
    {
        throw new \LogicException('Используйте \\Bitrix\\Iblock\\ElementTable');
    }

    public static function update($primary, array $data)
    {
        throw new \LogicException('Используйте \\Bitrix\\Iblock\\ElementTable');
    }

    public static function delete($primary)
    {
        throw new \LogicException('Используйте \\Bitrix\\Iblock\\ElementTable');
    }
}
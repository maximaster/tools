<?php

namespace Mx\Tools\Orm\Iblock;

use Bitrix\Main\Entity;

class ElementPropertyTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_iblock_element_property';
    }

    public static function getMap()
    {
        $map = array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => 'Идентификатор значения свойства',
            ),
            'IBLOCK_PROPERTY_ID' => array(
                'data_type' => 'integer',
                'title' => 'Идентификатор свойства',
            ),
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'title' => 'Идентификатор элемента',
            ),
            'PROPERTY' => array(
                'data_type' => 'Bitrix\Iblock\PropertyTable',
                'reference' => array('=this.IBLOCK_PROPERTY_ID' => 'ref.ID'),
            ),
            //TODO Связать именно с той сущностью, которая относится к конкретному инфоблоку
            //чтобы была возможность чайнить связи из значений свойств
            /*'ELEMENT' => array(
                'data_type' => 'Mx\Tools\Orm\Iblock\ElementTable',
                'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
            ),*/
            'VALUE' => array(
                'data_type' => 'string',
                'title' => 'Значение свойства',
            ),
            'VALUE_TYPE' => array(
                'data_type' => 'string',
                'title' => 'Тип свойства',
            ),
            'VALUE_ENUM' => array(
                'data_type' => 'integer',
                'title' => 'Значение свойства типа "Список"',
            ),
            'VALUE_NUM' => array(
                'data_type' => 'float',
                'title' => 'Числовое значение свойства',
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string',
                'title' => 'Описание значения свойства',
            ),
        );

        return $map;
    }
}
<?php

namespace Mx\Tools\Orm;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Mx\Tools\Helpers\IblockStructure;
use Mx\Tools\Orm\Iblock\ElementPropertyTable;
use Mx\Tools\Orm\Iblock\ElementPropMultipleTable;
use Mx\Tools\Orm\Iblock\ElementPropSingleTable;

class Query extends \Bitrix\Main\Entity\Query
{
    /**
     * @var array Массив с идентификаторами и кодами инфоблоков
     */
    private $iblockPrimary = array();

    protected function buildQuery()
    {
        $this->iblockRelatedData();
        return parent::buildQuery();
    }

    /**
     * Метод инициализирует поиск данных, связанных с инфоблоками и начинает добавление свойств к списку возможных
     */
    private function iblockRelatedData()
    {
        if ($this->searchIblocks())
        {
            $meta = $this->getMetaData();
            foreach ($meta as $data)
            {
                $this->appendProperties($data['iblock'], $data['properties']);
            }
        }
    }

    private function searchIblocks()
    {
        return $this->searchInFilter();
    }

    private function searchInFilter()
    {
        $i = new \RecursiveArrayIterator(array($this->filter));
        iterator_apply($i, array($this, 'recursiveScan'), array($i));
        return !empty($this->iblockPrimary);
    }

    private function recursiveScan(\RecursiveArrayIterator $iterator)
    {
        while ( $iterator->valid() )
        {
            $this->checkIblockData($iterator);

            if ( $iterator->hasChildren() )
            {
                $this->recursiveScan($iterator->getChildren());
            }
            else
            {
                $this->checkIblockData($iterator);
            }

            $iterator->next();
        }
    }

    private function checkIblockData(\Iterator $iterator)
    {
        $key = $iterator->key();
        $value = $iterator->current();

        if (strpos($key, 'IBLOCK_ID') !== false || strpos($key, 'IBLOCK_CODE') !== false)
        {
            if (is_array($value))
            {
                foreach ($value as $v)
                {
                    $this->iblockPrimary[ $v ] = $v;
                }

                return true;
            }
            else
            {
                $this->iblockPrimary[ $value ] = $value;
            }
            return true;
        }

        return false;
    }

    private function getMetaData()
    {
        $res = array();
        if (empty($this->iblockPrimary)) return $res;

        foreach ($this->iblockPrimary as $iblock)
        {
            $res[ $iblock ] = IblockStructure::full($iblock);
        }

        return $res;
    }

    private function appendProperties(array $iblock, array $props)
    {
        if (empty($props)) return;

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
            $isMultiple = $prop['MULTIPLE'] == 'Y';
            if ($isMultiple) continue;

            $newField = null;
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

                $newField = new ReferenceField(
                    'PROPERTY_' . $code,
                    $singleProp,
                    array(
                        '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                        '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $prop['ID'])
                    ),
                    array('join_type' => 'LEFT')
                );

                $this->init_entity->addField(
                    new ExpressionField(
                        'PROPERTY_' . $code . '_VALUE',
                        '%s',
                        "PROPERTY_{$code}.{$column}"
                    )
                );
            }
            else
            {
                $newField = new ReferenceField(
                    'PROPERTY_' . $code,
                    $singleProp,
                    array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                    array('join_type' => 'LEFT')
                );

                $this->init_entity->addField(
                    new ExpressionField(
                        'PROPERTY_' . $code . '_VALUE',
                        '%s',
                        "PROPERTY_{$code}.{$prop['CODE']}"
                    )
                );
            }

            if ($newField !== null)
                $this->init_entity->addField($newField);
        }
    }
}
<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Maximaster\Tools\Helpers\IblockStructure;
use Maximaster\Tools\Interfaces\IblockRelatedTableInterface;
use Bitrix\Main\Entity;

class SectionTable extends \Bitrix\Iblock\SectionTable implements IblockRelatedTableInterface
{
    public static function getIblockId()
    {
        return null;
    }

    public static function getMap()
    {
        if (static::getIblockId() === null) return parent::getMap();

        $map = parent::getMap();

        foreach (self::getAdditionalMap() as $key => $mapItem)
        {
            $map[] = $mapItem;
        }

        return $map;
    }

    private static function getAdditionalMap($iblockId = null)
    {
        $map = array();

        $iblockId = $iblockId === null ? static::getIblockId() : $iblockId;

        return $map;
        
        $uFields = IblockStructure::sectionUFields($iblockId);

        if (!$uFields)
            return $map;

        global $USER_FIELD_MANAGER;

        foreach ($uFields as $uField)
        {
            if ($uField['MULTIPLE'] == 'N')
            {
                // just add single field
                $field = $USER_FIELD_MANAGER->getEntityField($uField, $uField['FIELD_NAME']);
                $map[] = $field;

                foreach ($USER_FIELD_MANAGER->getEntityReferences($uField, $field) as $reference)
                {
                    $map[] = $reference;
                }
            }
            else
            {
                //TODO
                continue;
            }
        }

        return $map;
    }

    /**
     * @param $iblockId
     * @return Entity\Base
     * @throws ArgumentException
     */
    public static function compileEntity($iblockId)
    {
        $iblock = IblockStructure::iblock($iblockId);
        if (!$iblock)
        {
            throw new ArgumentException('Указан несуществующий идентификатор инфоблока');
        }

        $entityName = "Iblock" . Entity\Base::snake2camel($iblockId) . "SectionTable";
        $fullEntityName = '\\' . __NAMESPACE__ . '\\' . $entityName;

        $code = "
            namespace "  . __NAMESPACE__ . ";
            class {$entityName} extends SectionTable {
                public static function getIblockId(){
                    return {$iblock['ID']};
                }
            }
        ";
        if (!class_exists($fullEntityName)) eval($code);

        return Entity\Base::getInstance($fullEntityName);
    }
}
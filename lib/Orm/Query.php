<?php

namespace Mx\Tools\Orm;

use Mx\Tools\Helpers\IblockStructure;
use Mx\Tools\Interfaces\IblockElementTableInterface;
use Mx\Tools\Orm\Iblock\ElementTable;

/**
 * Ð Ð°ÑÑˆÐ¸Ñ€ÐµÐ½Ð½Ñ‹Ð¹ ÐºÐ»Ð°ÑÑ Ð·Ð°Ð¿Ñ€Ð¾ÑÐ°, ÐºÐ¾Ñ‚Ð¾Ñ€Ñ‹Ð¹ Ð¼Ð¾Ð¶ÐµÑ‚ Ð´Ð¾Ð±Ð°Ð²Ð¸Ñ‚ÑŒ Ð¿Ð¾Ð»Ñ Ð² ÑÑƒÑ‰Ð½Ð¾ÑÑ‚ÑŒ Ð¿Ñ€Ð¸ Ð½ÐµÐ¾Ð±Ñ…Ð¾Ð´Ð¸Ð¼Ð¾ÑÑ‚Ð¸
 * @package Mx\Tools\Orm
 */
class Query extends \Bitrix\Main\Entity\Query
{
    /**
     * @var array Ìàññèâ ñ èäåíòèôèêàòîðàìè è êîäàìè èíôîáëîêîâ
     */
    private $iblockPrimary = array();
    private $useIblockSearch = null;

    private function useIblockSearch()
    {
        if ($this->useIblockSearch !== null) return $this->useIblockSearch;

        $entityClass = $this->getEntity()->getDataClass();
        $this->useIblockSearch = $entityClass === '\\Mx\\Tools\\Orm\\Iblock\\ElementTable';

        return $this->useIblockSearch;
    }

    protected function buildQuery()
    {
        if ($this->useIblockSearch())
        {
            $this->appendIblockRelatedData();
        }
        else
        {
            $entityClass = $this->getEntity()->getDataClass();
            $this->filter['IBLOCK_ID'] = $entityClass::getIblockId();
        }

        return parent::buildQuery();
    }

    /**
     * Ìåòîä èíèöèàëèçèðóåò ïîèñê äàííûõ, ñâÿçàííûõ ñ èíôîáëîêàìè è íà÷èíàåò äîáàâëåíèå ñâîéñòâ ê ñïèñêó âîçìîæíûõ
     */
    private function appendIblockRelatedData()
    {
        if ($this->searchIblocks())
        {
            $maps = array();
            if (empty($this->iblockPrimary)) return $maps;

            foreach ($this->iblockPrimary as $iblockPrimary)
            {
                $iblock = IblockStructure::iblock($iblockPrimary);
                $maps[] = ElementTable::getAdditionalMap($iblock['ID']);
            }

            call_user_func_array(array($this, 'appendIblockFields'), $maps);
        }
    }

    private function searchIblocks()
    {
        array_walk_recursive($this->filter, array($this, 'checkForIblockData'));
        return !empty($this->iblockPrimary);
    }

    private function checkForIblockData($value, $key)
    {
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

    private function appendIblockFields()
    {
        $maps = func_get_args();
        if (count($maps) === 0) return;

        //TODO ÐŸÑ€Ð¾Ð²ÐµÑ€Ð¸Ñ‚ÑŒ Ð½Ð° Ð¿Ð¾Ð²Ñ‚Ð¾Ñ€ÑÑŽÑ‰Ð¸ÐµÑÑ ÑÐ²Ð¾Ð¹ÑÑ‚Ð²Ð° Ð¸ ÑÑƒÑ‰Ð½Ð¾ÑÑ‚Ð¸, Ñ‚.Ðº. Ð·Ð°Ð¿Ñ€Ð¾Ñ Ð¼Ð¾Ð¶ÐµÑ‚ Ð²Ñ‹Ð·Ñ‹Ð²Ð°Ñ‚ÑŒÑÑ Ð´Ð»Ñ Ð´Ð²ÑƒÑ… Ð¸Ð½Ñ„Ð¾Ð±Ð»Ð¾ÐºÐ¾Ð²
        foreach ($maps as $map)
        {
            foreach ($map as $field)
            {
                $this->init_entity->addField($field);
            }
        }
    }
}
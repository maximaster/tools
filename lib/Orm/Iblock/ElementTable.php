<?php

namespace Mx\Tools\Orm\Iblock;

use Bitrix\Main\Entity;
use Mx\Tools\Orm\Query;

class ElementTable extends \Bitrix\Iblock\ElementTable
{
    public static function query()
    {
        return new Query(static::getEntity());
    }
}
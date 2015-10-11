<?
namespace Mx\Tools\Orm\Iblock;
use Bitrix\Main\Entity;
use Bitrix\Main;

abstract class ElementPropMultipleTable extends Entity\DataManager
{
    protected static $iblockId;
    protected static $iblockCode;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element_prop_m' . static::$iblockId;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID',
            ),
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            /*new Entity\ReferenceField(
                'ELEMENT',
                '\Base',
                array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID')
            ),*/
            'IBLOCK_PROPERTY_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'required' => true,
            ),
            'VALUE_ENUM' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'VALUE_NUM' => array(
                'data_type' => 'float',
                'required' => true,
            ),
            new Entity\ReferenceField(
                'PROPERTY',
                '\Bitrix\Iblock\Property',
                array('this.IBLOCK_PROPERTY_ID' => 'ref.ID')
            ),
            new Entity\ExpressionField(
                'CODE',
                '%s',
                'PROPERTY.CODE'
            )
        );
    }

    public static function getInstance($iblockCode)
    {
        $meta = ElementTable::getIblockData($iblockCode);
        $iblock = $meta['iblock'];
        if (!$iblock)
        {
            throw new Main\ArgumentException('Указан код несуществующего инфоблока');
        }

        self::$iblockCode = $iblockCode;
        self::$iblockId = $iblock['ID'];

        return self::compileEntity();
    }

    private static function compileEntity()
    {
        $class = Entity\Base::snake2camel(static::$iblockCode) . 'MultiplePropertyTable';
        if (!class_exists($class))
        {
            $eval = "class {$class} extends " . __CLASS__ . "{}";

            eval($eval);
        }

        return new $class;
    }
}

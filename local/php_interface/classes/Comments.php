<?php

namespace Ameton;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;

class Comments
{
    const HL_BLOCK_CODE = "Comments";
    const IBLOCK_CODE = "News";

    protected int $hlBlockId = 0;
    protected $hlBlockEntity;

    /**
     * Comments constructor.
     * @param int $hlBlockId
     * @throws SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct()
    {
        Loader::includeModule("highloadblock");

        $this->hlBlockId = $this->getHLBlockId();

        if($this->hlBlockId == 0){
            throw new SystemException(sprintf('HLBlock %s is undefined', self::HL_BLOCK_CODE));
        }

        $hlblock = HighloadBlockTable::getById($this->hlBlockId)->fetch();
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $this->hlBlockEntity = $entity->getDataClass();
    }


    /**
     * Метод добавляет новый комментарий
     * Возвращает id добавленного элемента
     * @param $arFields
     * @return int
     * @throws SystemException
     * @throws \Exception
     */
    function add($arFields): int
    {
        $this->prepareMargins($arFields);

        $result = $this->hlBlockEntity::add($arFields);

        if (!$result->isSuccess()) {
            throw new SystemException($result->getErrorMessages());
        }

        return $result->getId();
    }


    /**
     * Метод возращает rightMargin по id элемента
     * @param $elementId
     * @return int
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    function getRightMarginById($elementId): int
    {
        if(!is_numeric($elementId)){
            throw new SystemException(sprintf("Element id: %d is undefined", $elementId));
        }

        $data = $this->hlBlockEntity::getList(array(
            "select" => array("ID", "UF_RIGHT"),
            "order" => array("ID" => "DESC"),
            "filter" => array("ID" => $elementId),
        ));

        if($arData = $data->Fetch()){
            return $arData['UF_RIGHT'];
        }
    }

    /**
     * Метод модефицирует margins в $arFields для нового элемента
     * @param $arFields
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    function prepareMargins(&$arFields)
    {
        if ($arFields['UF_DEPTH'] == 1) {
            $arFields['UF_LEFT'] = 1;
            $arFields['UF_RIGHT'] = 2;
            $arFields['ROOT_ID'] = false;
        } elseif ($arFields['UF_DEPTH'] > 1) {
            $parentRightMargin = $this->getRightMarginById($arFields['UF_PARENT']);
            $this->shiftMargin($arFields['UF_ROOT_ID'], $parentRightMargin);

            $arFields['UF_LEFT'] = $parentRightMargin;
            $arFields['UF_RIGHT'] = $parentRightMargin + 1;
        }
    }

    /**
     * Метод пересчитывает margin для всех элементов объединеных по $rootId
     * @param int $rootId
     * @param int $leftMargin
     * @throws \Bitrix\Main\DB\SqlQueryException
     * @throws SystemException
     */
    function shiftMargin(int $rootId, int $leftMargin)
    {
        if($rootId == false){
            throw new SystemException("Root element id is undefined");
        }

        $conn = Application::getConnection();
        $helper = $conn->getSqlHelper();
        $tableName = mb_strtolower(self::HL_BLOCK_CODE);

        $conn->queryExecute('UPDATE '.$helper->quote($tableName)
            .'SET UF_LEFT = UF_LEFT+2 '
            .'WHERE UF_LEFT>='.(string)$leftMargin.' '
            .'AND ( UF_ROOT_ID='.(string)$rootId.' OR ID='.(string)$rootId.' )'
        );

        $conn->queryExecute('UPDATE '.$helper->quote($tableName)
            .'SET UF_RIGHT = UF_RIGHT+2 '
            .'WHERE UF_RIGHT>='.(string)$leftMargin.' '
            .'AND ( UF_ROOT_ID='.(string)$rootId.' OR ID='.(string)$rootId.' )'
        );
    }

    function getHLBlockId(): int {
        $hlBlockId = 0;

        $arHlblocks = HighloadBlockTable::getList(
            array(
                "select" => array(
                    "ID"
                ),
                "filter" => array(
                    "=NAME" => self::HL_BLOCK_CODE
                )
            )
        );

        if ($arHlblock = $arHlblocks->fetch()) {
            $hlBlockId = $arHlblock["ID"];
        }

        return $hlBlockId;
    }

    public function buildComments(&$arResult, int $nPageSize = 10){
        /*
         * TODO
         * Завернуть в тегированный кеш
         */
        $nav = new PageNavigation("page");
        $nav->allowAllRecords(true)
            ->setPageSize($nPageSize)
            ->initFromUri();

        $arFilter = Array(
            "=UF_NEWS_ID" => $arResult['ID'],
            "UF_DEPTH" => 1,
        );

        $rsData = $this->hlBlockEntity::getList(array(
            "select" => array('ID', 'UF_NEWS_ID', 'UF_AUTHOR', 'UF_DATE', 'UF_TEXT', 'UF_LEFT', 'UF_RIGHT'),
            "count_total" => true,
            "offset" => $nav->getOffset(),
            "limit" => $nav->getLimit(),
            "order" => array("UF_RIGHT" => "DESC"),
            "filter" => $arFilter,
        ));

        $nav->setRecordCount($rsData->getCount());
        $arParents = [];

        while($data = $rsData->Fetch()) {
            $childCount = (int)(($data['UF_RIGHT'] - $data['UF_LEFT'] - 1 ) / 2);
            $data['CHILDS_COUNT'] = $childCount;
            if($childCount > 0){
                $arParents[] = $data['ID'];
            }
            $arResult['COMMENTS'][$data['ID']] = $data;
        }

        $rsChildData = $this->hlBlockEntity::getList(array(
            "select" => array('ID', 'UF_NEWS_ID', 'UF_ROOT_ID', 'UF_AUTHOR', 'UF_DEPTH', 'UF_DATE', 'UF_TEXT', 'UF_LEFT', 'UF_RIGHT', 'UF_PARENT'),
            "filter" => array('UF_PARENT' => $arParents),
        ));

        while($data = $rsChildData->Fetch()) {
            $childCount = (int)(($data['UF_RIGHT'] - $data['UF_LEFT'] - 1 ) / 2);
            $data['CHILDS_COUNT'] = $childCount;
            $arResult['COMMENTS'][$data['UF_PARENT']]['CHILDS'][] = $data;
        }

        $arResult['COMMENTS_NAV'] = $nav;
    }

    /**
     * @param $rootId
     * @param $leftMargin
     * @param $rightMargin
     * @param $depth
     * @return mixed
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    function getChilds($rootId, $depth, $leftMargin, $rightMargin)
    {
        /*
         * TODO
         * Завернуть в тегированный кеш
         */

        $arChilds = [];

        if(!is_numeric($rootId)){
            throw new SystemException(sprintf("Element id: %d is undefined", $rootId));
        }

        $data = $this->hlBlockEntity::getList(array(
            "select" => array('ID', 'UF_NEWS_ID', 'UF_ROOT_ID', 'UF_AUTHOR', 'UF_DEPTH', 'UF_DATE', 'UF_TEXT', 'UF_LEFT', 'UF_RIGHT', 'UF_PARENT'),
            "order" => array("ID" => "DESC"),
            "filter" => array(
                "UF_ROOT_ID" => $rootId,
                "UF_DEPTH" => $depth + 1,
                array(
                    "LOGIC" => "AND",
                    array(">UF_LEFT" => $leftMargin),
                    array("<UF_LEFT" => $rightMargin)
                )
            ),
        ));


        while($arData = $data->Fetch()){
            $childCount = (int)(($arData['UF_RIGHT'] - $arData['UF_LEFT'] - 1 ) / 2);
            $arData['CHILDS_COUNT'] = $childCount;
            $arChilds[] = $arData;
        }

        return $arChilds;
    }
}

<?php

namespace App;

use Bitrix\Catalog\StoreTable;
use Bitrix\Sale\StoreProductTable;
use Illuminate\Database\Eloquent\Model;
use Bitrix\Catalog\ProductTable;

class Product
{
    static public $IBLOCK_ID = 30;

    //
    static public function existProduct($prodId)
    {
        if (ProductTable::getById($prodId)->fetch()) {
            return true;
        }
        return false;
    }

    static public function getBySku($sku)
    {
        $arRes = \CIBlockElement::GetList(
            false,
            [
                'IBLOCK_ID' => 30,
                '=PROPERTY_CML2_ARTICLE' => $sku
            ],
            false,
            false,
            [
                'NAME',
                'ID',
                'IBLOCK_ID'
            ]
        );
        if( $arItem = $arRes->Fetch()) {
            return $arItem['ID'];
        }
        return false;

    }
}

<?php

namespace App;

use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Sale\StoreProductTable;
use Illuminate\Database\Eloquent\Model;
use Bitrix\Catalog\ProductTable;

class Price
{
    static public $IBLOCK_ID = 30;
    static public $RETAIL_PRICE_CODE = 1;
//    static public $EXTRA_ID = ; //Код (ID) типа наценки.

    static public function setPrice( $preOrder) {
//        dd(PriceTable::getMap());
        $arPrice = PriceTable::getList([
            'select' => ['*'],
            'filter' => ['PRODUCT_ID' => $preOrder['nnt'],'CATALOG_GROUP_ID' => 1]
        ])->fetch();
        PriceTable::update(
            $arPrice['ID'],
            [
                'PRICE' => $preOrder['prcRet']
            ]
        );
    }
    static public function setPriceProduct( $price, $prodId) {
//        dd(PriceTable::getMap());
        $arPrice = PriceTable::getList([
            'select' => ['*'],
            'filter' => ['PRODUCT_ID' => $prodId,'CATALOG_GROUP_ID' => 1]
        ])->fetch();
        if( $arPrice ) {
            PriceTable::update(
                $arPrice['ID'],
                [
                    'PRICE' => $price
                ]
            );
        }else {
            $ret = PriceTable::add(
                [
                    'CATALOG_GROUP_ID' => 1,
                    'CURRENCY' => 'RUB',
                    'PRODUCT_ID' => $prodId,
                    'PRICE' => $price,
                    'PRICE_SCALE' => $price,
                ]
            );
        }
    }

}

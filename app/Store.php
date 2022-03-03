<?php

namespace App;

use Bitrix\Catalog\StoreTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Sale\ProductTable;
use Illuminate\Database\Eloquent\Model;
use mysql_xdevapi\Exception;

class Store
{
    private $storeId;
    private $storeApiCode;
    public $error = null;

    public function getStore($storeApiCode)
    {
        $this->storeApiCode = $storeApiCode;
        $this->error = '';
        $rsStoreProduct = \Bitrix\Catalog\StoreTable::getList(
            [
                'filter' => ['UF_API_CODE' => $storeApiCode],
                'select' => ['*', 'UF_API_CODE']
            ]);
        $arStore = $arProductStore = $rsStoreProduct->fetch();
        if ($arStore) {
            $this->storeId = $arStore['ID'];
            return true;
        }
        $this->error = 'Store does not exist';
        return false;
    }

    /**
     * @param $prodId - product ID
     * @param $storeId - store ID
     * @param $qny - quantity
     */
    static public function setStoreQny($prodId, $storeId, $qnt)
    {
        $rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(
            [
                'filter' => ['=STORE_ID' => $storeId, '=PRODUCT_ID' => $prodId],
            ]);
        if ($arProductStore = $rsStoreProduct->fetch()) {
            try {
                $qnt = $arProductStore['AMOUNT'] + $qnt;
                $id = \Bitrix\Catalog\StoreProductTable::update(
                    $arProductStore['ID'],
                    [
                        'PRODUCT_ID' => $prodId,
                        'STORE_ID' => $storeId,
                        'AMOUNT' => $qnt,
                    ]);
                if (!$id) {
                    return false;
                }
            } catch (Exception  $e) {
                return false;
            }
            Store::updateStoreQny($prodId);

        } else {
            try {
                $id = \Bitrix\Catalog\StoreProductTable::add([
                    'PRODUCT_ID' => $prodId,
                    'STORE_ID' => $storeId,
                    'AMOUNT' => $qnt,
                ]);
                if (!$id) {
                    return false;
                }
            } catch (Exception  $e) {
                return false;
            }
            Store::updateStoreQny($prodId);
        }
    }


    /**
     * @param $prodId
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    static public function updateStoreQny($prodId)
    {
        $rsStoreProduct = \Bitrix\Catalog\StoreProductTable::getList(
            [
                'filter' => ['=PRODUCT_ID' => $prodId, 'STORE.ACTIVE' => 'Y']
            ]);
        $amount = 0;
        while ($arProductStore = $rsStoreProduct->fetch()) {
            $amount += $arProductStore['AMOUNT'];
        }
        $updateQuantity = \Bitrix\Catalog\ProductTable::update(
            $prodId,
            [
                'QUANTITY' => $amount,
                'AVAILABLE' => ($amount > 0) ? 'Y' : 'N'
            ]);
    }

    /**
     * @param $storeApiCode
     * @return bool|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    static public function existStore($storeApiCode)
    {
        $rsStoreProduct = \Bitrix\Catalog\StoreTable::getList(
            [
                'filter' => ['UF_API_CODE' => $storeApiCode],
                'select' => ['*', 'UF_API_CODE']
            ]);
        $arStore = $arProductStore = $rsStoreProduct->fetch();
        if ($arStore) {
            return $arStore['ID'];
        }
        return false;
    }

    public function deleteAllStock()
    {
        $arRes = \Bitrix\Catalog\StoreProductTable::getList([
                'select' => ['ID', 'PRODUCT_ID', 'AMOUNT'],
                'filter' => ['STORE_ID' => $this->storeId]
            ]
        );
        while ($arStore = $arRes->fetch()) {
            $id = \Bitrix\Catalog\StoreProductTable::update(
                $arStore['ID'],
                [
                    'AMOUNT' => 0,
                ]);
            Store::updateStoreQny($arStore['PRODUCT_ID']);
        }
    }
    public static function deleteStock($prodId, $storeId)
    {
        $arRes = \Bitrix\Catalog\StoreProductTable::getList([
                'select' => ['ID', 'PRODUCT_ID', 'AMOUNT'],
                'filter' => ['STORE_ID' => $storeId, 'PRODUCT_ID' => $prodId ]
            ]
        )->fetch();
        if( $arRes ) {
            $id = \Bitrix\Catalog\StoreProductTable::update(
                $arRes['ID'],
                [
                    'PRODUCT_ID' => $prodId,
                    'STORE_ID' => $storeId,
                    'AMOUNT' => 0,
                    ]);
        }

//        while ($arStore = $arRes->fetch()) {
//            $id = \Bitrix\Catalog\StoreProductTable::update(
//                $arStore['ID'],
//                [
//                    'AMOUNT' => 0,
//                ]);
//            Store::updateStoreQny($arStore['PRODUCT_ID']);
//        }
    }

    public function getStocks()
    {
        $stocks = [];
        $arRes = \Bitrix\Catalog\StoreProductTable::getList([
                'select' => ['ID', 'PRODUCT_ID', 'AMOUNT'],
                'filter' => ['STORE_ID' => $this->storeId]
            ]
        );
        while ($arStore = $arRes->fetch()) {
            $element = \Bitrix\Iblock\Elements\ElementCatalogTable::getByPrimary(
                $arStore['PRODUCT_ID'],
                [
                    'select' => ['ID', 'CML2_ARTICLE'],
                ]
            )->fetchObject();
            if( $element && $element->getCml2Article()) {
                $stocks [] = [
                    'storeId' => $this->storeApiCode,
                    "nnt" => $element->getId(),
                    'sku' => $element->getCml2Article()->getValue(),
                    'qnt' => $arStore['AMOUNT'],
                ];
            }
        }
        return $stocks;
    }
}

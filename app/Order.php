<?php

namespace App;

use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\OrderTable;
use Bitrix\Sale\StoreProductTable;
use Illuminate\Database\Eloquent\Model;
use Bitrix\Catalog\ProductTable;

class Order
{
    static $statusBxToAsna = [
        'F' => 110, // Выкуплен
        'N' => 100, // Новый
        'B' => 111 // Отменен
    ];
    // Новый, Принят, в сборке, собран,изменён, резерв сброшен, отменён, закрыт
    static $statusAsnaToBx = [
        200 => 'A', //Заказ принят аптекой. Формируется на заголовок
        201 => 'P', //Заказ частично принят аптекой
        202 => 'R', //Отказ со стороны аптеки
        204 => 'J', //
        205 => 'D', // Резерв сброшен
        210 => 'F', //Заказ выкуплен
        211 => 'E', //Отмена заказа подтверждена
        212 => 'B', // Заказ отменен аптекой
        213 => 'C', // Заказ собран (укомплектован)
        100 => 'N', // Заказ собран (укомплектован)
    ];

    static public function getOrders($storeId, $orderId, $since)
    {
        date_default_timezone_set("Europe/Moscow");

        $orders = [];
        $headers = [];
        $rows = [];
        $statuses = [];
        $filter = [];
        $filter['STATUS_ID'] = ['N','B']; // Выбираем только новый и отмененный заказ
        if (!is_null($orderId) ) {
            $filter['ID'] = $orderId;
        }
        if (!is_null($since)) {
            $objDateTime = new \DateTime($since);
            $objDateTime = DateTime::createFromPhp($objDateTime);
            $objDateTime->setTimezone(new \DateTimeZone("Europe/Moscow"));
            $filter['>DATE_INSERT'] = $objDateTime;
        }

        $arRes = \Bitrix\Sale\Order::getList([
            'select' => ['*',
                'NAME' => 'USER.NAME',
                'LAST_NAME' => 'USER.LAST_NAME',
                'SECOND_NAME' => 'USER.SECOND_NAME',
                'PHONE' => 'USER.PERSONAL_PHONE',
                'EMAIL' => 'USER.EMAIL'],
            'filter' => $filter,
        ]);
        while ($arOrder = $arRes->fetch()) {
            if( $arOrder['CANCELED'] == 'Y' ) {
                continue;
            }
            $date = $arOrder['DATE_INSERT'];
            $dateUTC = clone $date;
            $dateUTC->setTimeZone(new \DateTimeZone('UTC'));
//dd(json_encode($arOrder['NAME'],JSON_UNESCAPED_UNICODE));
//            $date->setDefaultTimeZone(new \DateTimeZone('Europe/Moscow'));
//            $timeStamp = $date->getTimestamp();
//            $dateN = DateTime::createFromTimestamp($timeStamp);;

//            $date->setTimeZone(new \DateTimeZone('UTC'));
            $order = \Bitrix\Sale\Order::load($arOrder['ID']);
            $propertyCollection = $order->getPropertyCollection();
            $phone = $propertyCollection->getPhone()->GetValue();
            $arName = [];
            if( $arOrder['LAST_NAME'] ) {
                $arName [] = $arOrder['LAST_NAME'];
            }
            if( $arOrder['NAME'] ) {
                $arName [] = $arOrder['NAME'];
            }
            if( $arOrder['SECOND_NAME'] ) {
                $arName [] = $arOrder['SECOND_NAME'];
            }
            $header = [
                'orderId' => $arOrder['ID'],  //*
                'storeId' => $storeId,
                'issuerId' => $storeId, //Уникальный код аптеки, которая выдает заказ (для самовывоза)
                'num' => $arOrder['ACCOUNT_NUMBER'],  //*
                'date' => $date->format('c'),  //*
                "name" => implode(' ',$arName),  //*
                "mPhone" => $phone, //*
                "payType" => "Наличный расчет", //Тип оплаты
                "payTypeId" => 0, //ИД типа оплаты по справочнику АСНА
                "dCard" => "", //Дисконтная карта
                "src" => $_SERVER['SERVER_NAME'],
                "ae" => 0, //Признак АСНА-Экономия (0 - нет, 1 - да). Заказ с признаком АСНА-Экономия не должен редактироваться через клиентское ПО
                "unionId" => "", //ИД совместной покупки
                "ts" => $dateUTC->format('c'), //*Отметка времени, в формате ISO8601. Представляет собой дату и время создания или обновления заголовка сервисом АСНА(в нулевом часовом поясе)
                "delivery" => false, //Признак доставки (true - необходима доставка, false - самовывоз)
//                "deliveryInfo" => "{\"address\":\"ул.Докукина, 15\"}"
            ];
//            $orders['headers'][] = $order;
            $headers [] = $header;
            $orderObj = \Bitrix\Sale\Order::load($arOrder['ID']); //по ID заказа
            $basket = $orderObj->getBasket();
            $arBasketItems = $basket->getBasketItems();
            //из строк: rowId, orderId, nnt, qnt, prc, supInn, dlvDate, ts
            foreach ($arBasketItems as $rowId => $basketItem) {
                $date = $basketItem->getField('DATE_UPDATE');
                $dateUTC = clone $date;
                $dateUTC->setTimeZone(new \DateTimeZone('UTC'));
//                $orders['rows'][] = [
                    $rows [] = [
                    'rowId' => $rowId,
                    'orderId' => $arOrder['ID'],
                    'nnt' => $basketItem->getProductId(),
                    'qnt' => $basketItem->getQuantity(),
                    //getFinalPrice
                    'prc' => $basketItem->getPrice(),
                    "dtn" => 0.0,
                    "prcLoyal" => 0.0,
                    'ts' =>  $dateUTC->format('c'),
                ];
            }
            $status = array_search($arOrder['STATUS_ID'],Order::$statusAsnaToBx);
            if( $arOrder['CANCELED'] == 'Y' ) {
                $status = 111;
            }
            $cmnt = '';
            if( $arOrder['USER_DESCRIPTION']) {
                $cmnt = $arOrder['USER_DESCRIPTION'];
            }
            $orderStatus = [
                'statusId' => '',
                'orderId' => $arOrder['ID'],
                'storeId' => $storeId,
                'date' => $date->format('c'),
                'status' => $status,
                'rcDate' => $date->add('2 day')->format('c'),
                'cmnt' => $cmnt,
                'ts' => $dateUTC->format('c')
            ];
            $statuses [] = $orderStatus;
//            $orders['statuses'][] = $orderStatus;
        }
        $orders['headers'] = $headers;
        $orders['rows'] = $rows;
        $orders['statuses'] = $statuses;

        return $orders;
    }

    static function setOrdersStatus($arStatus) {
        $map = \Bitrix\Sale\Internals\OrderTable::getMap();
        foreach ($arStatus as $status ) {
//            dd(Order::$statusAsnaToBx[$status['status']]);
            $arOrder = \Bitrix\Sale\Internals\OrderTable::getList([
                'select' => ['*'],
                'filter' => ['ID' => $status['orderId']]
            ])->fetch();
            if( $arOrder) {
                \Bitrix\Sale\Internals\OrderTable::update(
                    $arOrder['ID'],
                    [
                        'STATUS_ID' => Order::$statusAsnaToBx[$status['status']]
                    ]
                );
            }
        }

    }
}

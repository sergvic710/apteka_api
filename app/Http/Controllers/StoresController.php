<?php
namespace App\Http\Controllers;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../');
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';


use App\Order;
use App\Price;
use App\Product;
use App\Store;
use CUser;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Controller;
use http\Env\Response;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use function Symfony\Component\Translation\t;

class StoresController extends Controller
{
    public function Stock(Request $request,$storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        if( !$sId=Store::existStore($storeId)) {
            return response()->json(['error' => 'Store does not exist'], 400);
        }
        // Обнуляем все остатки по складу
        $store = new Store();
        if( !$store->getStore($storeId) ) {
            return response()->json(['error' => $store->error ], 400);
        }
        $store->deleteAllStock();

        // Заносим остатки
        $priceProduct = [];
        if( count($request->stocks) > 0 ) {
            foreach ($request->stocks as $stock ) {
                if( !$prodId=Product::getBySku($stock['sku'])) {
                    return response()->json(['error' => 'SKU does not exist'], 400);
                }
                if( Store::setStoreQny($prodId, $sId,$stock['qnt'])) {
                    return response()->json(['error' => 'Error there updating stock'], 400);
                }
                if( $stock['prcRet']) {
                    if( array_key_exists($prodId,$priceProduct ) ) {
                        if( $stock['prcRet'] < $priceProduct[$prodId]) {
                            $priceProduct[$prodId] = $stock['prcRet'];
                        }
                    } else {
                        $priceProduct[$prodId] = $stock['prcRet'];
                    }
                }
            }
            if( !empty($priceProduct)) {
                foreach ($priceProduct as $prodId => $price) {
                    Price::setPriceProduct($price, $prodId);
                }
            }
            return response()->json(['message' => 'Success'], 200);
        }
        return response()->json(['error' => 'Not found stocks'], 400);
    }

    public function StockParth(Request $request,$storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        if( !$sId=Store::existStore($storeId)) {
            return response()->json(['error' => 'Store does not exist'], 400);
        }
        // Заносим остатки
        if( count($request->stocks) > 0 ) {
            $prodQny = [];
            $priceProduct = [];
            foreach ($request->stocks as $stock ) {

                if( !$prodId=Product::getBySku($stock['sku'])) {
                    return response()->json(['error' => 'SKU does not exist'], 400);
                }
                if( $stock['prcRet']) {
                    if( array_key_exists($prodId,$priceProduct ) ) {
                        if( $stock['prcRet'] < $priceProduct[$prodId]) {
                            $priceProduct[$prodId] = $stock['prcRet'];
                        }
                    } else {
                        $priceProduct[$prodId] = $stock['prcRet'];
                    }
                }
                if( array_key_exists($prodId,$prodQny ) ) {
                    $prodQny[$prodId] = $prodQny[$prodId] + $stock['qnt'];
                } else {
                    $prodQny[$prodId] = $stock['qnt'];
                }
//                if( Store::setStoreQny($prodId, $sId,$stock['qnt'])) {
//                    return response()->json(['error' => 'Error there updating stock'], 400);
//                }
            }
            if( !empty($priceProduct)) {
                foreach ($priceProduct as $prodId => $price) {
                    Price::setPriceProduct($price, $prodId);
                }
            }

            if( !empty($prodQny) ) {
                foreach ($prodQny as $prodId => $qny) {
                    Store::deleteStock($prodId,$sId);
                    Store::setStoreQny($prodId, $sId,$qny);
                }
            }
            return response()->json(['message' => 'Success'], 202);
        }
        return response()->json(['error' => 'Not found stocks'], 400);
    }

    /**
     * @param Request $request
     * @param $storeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAll(Request $request,$storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        $store = new Store();
        if( !$store->getStore($storeId) ) {
            return response()->json(['error' => $store->error ], 400);
        }
        $store->deleteAllStock();
        return response()->json(['message' => 'Success'], 200);
    }

    /**
     * @param Request $request
     * @param $storeId
     */
    public function getStock(Request $request,$storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        $store = new Store();
        if( !$store->getStore($storeId) ) {
            return response()->json(['error' => $store->error ], 400);
        }
        $stocks = $store->getStocks();
        return response()->json($stocks,200);
//        return $stocks;
    }

    /**
     * @param Request $request
     * @param $storeId
     * @return array
     */
    public function getOrders(Request $request,$storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        $orders = Order::getOrders($storeId,$request->get('orderId'), $request->get('since'));
        return response()->json($orders,200,[],JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @param $storeId
     */
    public function setStatusOrders(Request $request,$storeId='') {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        if( $storeId != '') {
            if( !Store::existStore($storeId) ) {
                return response()->json(['Message' => 'Store not found'],400,[],JSON_UNESCAPED_UNICODE);
            }
            if( count($request->statuses)) {
                Order::setOrdersStatus($request->statuses);
//                dd($request->statuses);
            }
        }else {
            return response()->json(['Message' => 'Store not set'],400,[],JSON_UNESCAPED_UNICODE);
        }
        return response()->json(['Message' => 'Success.'],201);
    }


}

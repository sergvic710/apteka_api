<?php
namespace App\Http\Controllers;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../');
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Price;
use App\User;
use Illuminate\Http\Request;
\Bitrix\Main\Loader::includeModule('iblock');

class EntitiesController extends Controller
{
    public function preorders(Request $request, $storeId) {
        if( !User::checkStore($storeId) ) {
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        if( count($request->preorders) > 0 ) {
            foreach ($request->preorders as $preorder ) {
                if( $preorder['prcRet']) {
                    Price::setPrice($preorder);
                }
            }
            return response()->json(['message' => 'Success'], 200);
        } else {
            return response()->json(['error' => 'Not found preorders'], 400);
        }
    }
}

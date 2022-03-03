<?php
namespace App\Http\Controllers;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../');
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';


use App\Product;
use App\Store;
use CUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use function Symfony\Component\Translation\t;
\Bitrix\Main\Loader::includeModule('iblock');

class ReferencesController extends Controller
{
    public function goodLinks(Request $request) {
        $name = false;
        $elements = [];
        if( $request->get('name') == 'true') {
            $name = true;
        }
        $arRes = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID'=>30,
                '=ACTIVE' => 'Y',
                '!PROPERTY_CML2_ARTICLE' => false
            ],
        false,
        false,
        [
            'ID', 'NAME','PROPERTY_CML2_ARTICLE'
        ]);
        if($arRes) {
            while ($arItem = $arRes->Fetch()) {
                $aa=10;
                if( $name ) {
                    $elements [] = [
                        'nnt' => $arItem['ID'],
                        'sku' => $arItem['PROPERTY_CML2_ARTICLE_VALUE'],
                        'name' => $arItem['NAME']
                    ];
                } else {
                    $elements [] = [
                        'nnt' => $arItem['ID'],
                        'sku' => $arItem['PROPERTY_CML2_ARTICLE_VALUE']
                    ];
                }
            }
        }
        return $elements;
    }
}

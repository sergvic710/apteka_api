<?php
namespace App\Http\Controllers;
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    //
    public function index() {
        echo "test'";
        $res = \CIBlockElement::GetList([],['IBLOCK_ID' => 38 ]);
        $item = $res->Fetch();
        return $item;
    }
    public function create( Request $request ) {
        $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|min:6',
        ]);
        $request['password'] = $request->client_secret;
        $request['name'] = $request->client_id;
        $request['email'] = '';
        $request['password'] = Hash::make($request->client_secret);
        $user = User::where('name', $request->client_id)->first();
        if( !$user ) {
            $user = User::create($request->toArray());
            if ($user) {
                return response()->json(['message' => 'success']);
            } else {
                return response()->json(['message' => 'error'],400);
            }
        }else {
            return response()->json(['message' => 'user is exist'],400);
        }
    }
}

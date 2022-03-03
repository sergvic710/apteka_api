<?php
namespace App\Http\Controllers;

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../');
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use CUser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use function Symfony\Component\Translation\t;

class TokenController extends Controller
{
    public function token(Request $request)
    {
        $credentials = request(['name', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);

        $userBx = new CUser();
        $login = $userBx->Login(
            $request->name,
            $request->password
        );
        if ($login === true) {
            $rsUser = CUser::GetByLogin($request->name);
            $arUser = $rsUser->Fetch();

            $user = User::where('name', $request->name)->first();
            if (!$user) {

                $request['password'] = Hash::make($request->password);
                $request['email'] = $arUser['EMAIL'];
                $request['bx_userID'] = $arUser['ID'];
                $user = User::create($request->toArray());
            }
            $credentials = request(['name', 'password']);

            if (! $token = auth()->attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return $this->respondWithToken($token);

//            $token = $user->createToken('Laravel Password Grant Client')->accessToken;
//                $response = [
//                    'token' => $token,
//                    "expires_in" => 3600,
//                    "token_type" => "Bearer",
//
//                ];
//                return response($response, 200);
        } else {
            return response($login, 400);
        }

    }
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

}

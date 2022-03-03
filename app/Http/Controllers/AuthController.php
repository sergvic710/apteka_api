<?php

namespace App\Http\Controllers;
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../../../');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use CUser;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        //    $this->middleware('auth:api', ['except' => ['login','token']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = $request->validate([
            'client_id' => 'required|string|max:255',
//            'email' => 'required|string|email|max:255|unique:users',
            'client_secret' => 'required|string|min:6',
        ]);
        $request['password'] = $request->client_secret;
        $request['name'] = $request->client_id;
        $credentials = request(['name', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            // Check user in the table user
            $user = User::where('name', $request->client_id)->first();
            if (!$user) {
                // User does not in the table user
                // User checking in the Bitrix
                $userBx = new CUser();
                $login = $userBx->Login(
                    $request->client_id,
                    $request->client_secret
                );
                if ($login === true) {
                    if ($token = auth()->attempt($credentials)) {
                        return $this->respondWithToken($token);
                    }
                    // Login is success
                    $rsUser = CUser::GetByLogin($request->client_id);
                    $arUser = $rsUser->Fetch();
                    if (!$user) {
                        $request['password'] = Hash::make($request->client_secret);
                        $request['email'] = $arUser['EMAIL'];
                        //$request['bx_userID'] = $arUser['ID'];
                        $user = User::create($request->toArray());
                        if ($user) {
                            if ($token = auth()->attempt($credentials)) {
                                return $this->respondWithToken($token);
                            }
                        }
                    }
                }
            }
            return response()->json(['error' => 'Unauthorized'], 400);
        }
        return $this->respondWithToken($token);
    }

    public function token(Request $request)
    {
        $validator = $request->validate([
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|min:6',
        ]);
        $request['password'] = $request->client_secret;
        $request['name'] = $request->client_id;
        $credentials = request(['name', 'password']);

        // Check user in the table user
//            $user = User::where('name', $request->client_id)->first();
//            if (!$user) {
        // User does not in the table user
        // User checking in the Bitrix

        $userBx = new CUser();
        $login = $userBx->Login(
            $request->name . "@api.local",
            $request->password
        );
        if ($login === true) {
            if (!$token = auth()->attempt($credentials)) {
                // Login is success
                $rsUser = CUser::GetByLogin($request->name . "@api.local");
                $arUser = $rsUser->Fetch();
                $userGr = CUser::GetUserGroup($arUser['ID']);
                if( array_search(10,$userGr) === false) {
                    return response()->json(['error' => 'Unauthorized'], 400);
                }
//                if (!$user) {
                $request['password'] = Hash::make($request->client_secret);
                $request['email'] = $arUser['EMAIL'];
                //$request['bx_userID'] = $arUser['ID'];
                $isUser = User::where('name', $request->name)->first();
                if ($isUser) {
                    $isUser->update($request->toArray());
                } else {
                    $user = User::create($request->toArray());
                }
                if ($token = auth()->attempt($credentials)) {
                    return $this->respondWithToken($token);
                }
            } else {
                return $this->respondWithToken($token);
            }
        }
        return response()->json(['error' => 'Unauthorized'], 400);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
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
     * @param string $token
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

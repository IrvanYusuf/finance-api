<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        try {
            $validated = Validator::make(
                $request->json()->all(),
                [
                    'name' => ['required', 'min:3'],
                    'email' => 'required|email|unique:users',
                    'password' => 'required|min:6',
                ]
            );

            if ($validated->fails()) {
                return ApiResponse::error(
                    'Validasi gagal',
                    $validated->errors(),
                    422
                );
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'admin'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;
            $payload = [
                'user' => $user,
                'token' => $token
            ];
            return ApiResponse::success($payload);
        } catch (\Throwable $th) {
            return ApiResponse::error('internal server error', $th->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $validated = Validator::make(
            $request->json()->all(),
            [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]
        );

        if ($validated->fails()) {
            return ApiResponse::error(
                'Validasi gagal',
                $validated->errors(),
                422
            );
        }
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // return response()->json(['message' => 'Login gagal'], 401);
            return ApiResponse::error(
                'Invalid Credentials',
                ['email' => ['Invalid Credentials']],
                401
            );
        }
        $token = $user->createToken($request->email)->plainTextToken;
        $payload = [
            'user' => $user,
            'token' => $token
        ];
        return ApiResponse::success($payload);
    }



    public function me(Request $request)
    {
        $payload = [
            'user' => $request->user(),
        ];
        return ApiResponse::success($payload);
    }



    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return ApiResponse::success(null, 'success logout');
        } catch (\Throwable $th) {
            return ApiResponse::error('error while logout', $th->getMessage(), 500);
        }
    }
}

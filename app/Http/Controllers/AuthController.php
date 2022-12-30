<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as HttpResponse;


class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user['password'])) {
            return response([
                'message' => 'Bad creds'
            ], 401);
        }
        
        $token = $user->createToken('myapptoken')->accessToken;

        $respone = [
            'user' => $user,
            'token' => $token
        ];

        return response($respone, 201);
    }
}

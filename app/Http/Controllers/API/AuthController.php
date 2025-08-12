<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginAuthRequest;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginAuthRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return success_response([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user' => $user
        ], 'Login success');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return success_response(null, 'Logout success');
    }
}

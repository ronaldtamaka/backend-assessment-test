<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller as BaseController;

class UserController extends BaseController
{

    public function register(){
        // dd('ff');
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        
        ]);
        // die('ttd');

        if($validator->fails()){
            return response()->json($validator->messages(), 422);
        }

        $user = User::create([
           'name' => request('name'),
           'email' => request('email'),
           'password' => Hash::make(request('password'))
        ]);

        return response()->json(['message' => 'Pendaftaran Anda Berhasil']);
    }
    
}

<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
            $token = explode('|', $token)[1];
            return response()->json([
                'success' => true,
                'response' => [
                    'token' => $token,
                    'user' => $user
                ]
            ]);
        }
        else{
            return response()->json(['success' => false, 'message' => 'Identifiants incorrects']);
        }
    }

}

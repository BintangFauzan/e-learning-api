<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){
       try{
         $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed',
            'role' => 'required|in:mahasiswa,dosen', // Memastikan role valid
        ]);

        $user = User::query()->create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

         return response()->json([
                'Status' => true,
                'message' => 'User berhasil ditambahkan',
                'data' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer'
                ]
            ]);
       }catch(Exception $exception){
        return response()->json([
            'Status' => false,
            'Message' => 'Gagal register',
            'Error' => $exception->getMessage()
        ]);
       }
    }

    public function login(Request $request){
        $validator = Validator::make($request->all(),[
            "email" => 'required',
            'password' => 'required'
        ]);
        
        if ($validator -> fails()){
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'password');

        if(!Auth::attempt($credentials)){
            return response()->json([
                'status' => false,
                "message" => "email atau password salah",
            ], 401);
        }

    $user = Auth::user();
    $user->tokens()->delete();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'login berhasil',
        'user' => $user,
        'authorisation' => [
            'token' => $token,
            'type' => 'bearer'
        ]
    ]);
    }

     public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Berhasil logout',
        ]);
    }
}

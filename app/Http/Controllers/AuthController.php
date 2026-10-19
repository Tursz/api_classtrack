<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Conta ainda não verificada',
                'email_not_verified' => true, // 👈 flag pro front redirecionar
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['data' => $user, 'token' => $token]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function logout(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->tokens()->delete();
        return response()->noContent();
    }

}

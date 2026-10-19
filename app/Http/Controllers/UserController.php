<?php

namespace App\Http\Controllers;

use App\Mail\PinCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse|Response
    {
        if (!$users = User::all()) {
            return response()->json(['message' => 'Nenhum usuário'], Response::HTTP_NO_CONTENT);
        }

        return response()->json(['message' => 'Todos os usuários', 'data' => $users], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'O campo senha é obrigatório.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $pinCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'pin_code' => $pinCode,
            'pin_code_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new PinCodeMail($pinCode, $user->name));

        return response()->json([
            'message' => 'Usuário criado com sucesso. Verifique seu e-mail para confirmar a conta.',
            'data' => ['email' => $user->email],
        ], Response::HTTP_CREATED);
    }

    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'pin_code' => ['required', 'digits:6'],
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'pin_code.required' => 'O código PIN é obrigatório.',
            'pin_code.digits' => 'O código PIN deve conter exatamente 6 dígitos.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Conta já verificada'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->pin_code !== $request->pin_code) {
            return response()->json(['message' => 'Código inválido'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Carbon::now()->greaterThan($user->pin_code_expires_at)) {
            return response()->json(['message' => 'Código expirado. Solicite um novo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'email_verified_at' => Carbon::now(),
            'pin_code' => null,
            'pin_code_expires_at' => null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Conta verificada com sucesso',
            'token' => $token,
            'data' => $user,
        ], Response::HTTP_OK);
    }

    public function resendPin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'O campo e-mail é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado'], Response::HTTP_NOT_FOUND);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Conta já verificada'], Response::HTTP_BAD_REQUEST);
        }

        $pinCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'pin_code' => $pinCode,
            'pin_code_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        Mail::to($user->email)->send(new PinCodeMail($pinCode, $user->name));

        return response()->json(['message' => 'Novo código enviado para o seu e-mail'], Response::HTTP_OK);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(['message' => 'Usuário encontrado', 'data' => $user], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => ['required'],
            'email' => ['email'],
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'email.email' => 'Informe um endereço de e-mail válido.',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email
        ]);

        $user->save();
        return response()->json(['message' => 'Usuário atualizado', 'data' => $user]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}

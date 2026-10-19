<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LearnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $leaners = $user->learners;

        return response()->json(['message' => 'Alunos cadastrados', 'data' => $leaners], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['nullable', 'max:255', 'email'],
            'contact' => ['nullable', 'max:22'],
            'observation' => ['nullable'],
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'contact.max' => 'O contato não pode ter mais de 22 caracteres.',
        ]);

        $leaner = Learner::create([
            'user_id' => $request->user()->id,
            'plan_id' => $request->plan_id,
            'name' => $request->name,
            'email' => $request->email,
            'contact' => $request->contact,
            'observation' => $request->observation,
        ]);

        return response()->json(['message' => 'Aluno cadastrado com sucesso', 'data' => $leaner], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Learner $learner): Response|JsonResponse
    {
        if (!$learner) {
            return response()->json(['message' => 'Aluno não encontrado'])->noCotent();
        }
        return response()->json(['message' => 'Aluno encontrado', 'data' => $learner], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Learner $learner)
    {
        $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['nullable', 'max:255', 'email'],
            'contact' => ['nullable', 'max:22'],
            'observation' => ['nullable'],
        ], [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.max' => 'O e-mail não pode ter mais de 255 caracteres.',
            'contact.max' => 'O contato não pode ter mais de 22 caracteres.',
        ]);

        $user = $request->user();
        if (!$user->id === $learner->user_id) {
            return response()->json(['message' => 'O professor não tem acesso a esse aluno'], Response::HTTP_UNAUTHORIZED);
        }

        $learner->update([
            'plan_id' => $request->plan_id,
            'name' => $request->name,
            'email' => $request->email,
            'contact' => $request->contact,
            'observation' => $request->observation,
        ]);

        return response()->json(['message' => 'Aluno atualizado com sucesso', 'data' => $learner], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Learner $learner): JsonResponse
    {
        if (!$request->user()->id === $learner->user_id) {
            return response()->json(['message' => 'O aluno não pertence a esse professor'], Response::HTTP_UNAUTHORIZED);
        }

        $learner->delete();
        $learner->save();

        return response()->json(['message' => 'Aluno excluído com sucesso'])->noContent();
    }
}

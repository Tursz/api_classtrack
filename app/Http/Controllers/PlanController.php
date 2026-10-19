<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $user = $request->user();
        if (!$plans = $user->plans) {
            return response()->noContent();
        }

        return response()->json(['message' => 'Planos encontrados', 'data' => $plans], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'description' => ['sometimes'],
            'value' => ['required', 'numeric'],
        ]);

        $plan = Plan::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'value' => $request->value,
        ]);

        return response()->json(['message' => 'Plano criado com sucesso', 'data' => $plan], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Plan $plan)
    {
        if (!$request->user()->id == $plan->user_id) {
            return response()->json(['message' => 'O usuário não tem permissão para ver esse plano'], Response::HTTP_UNAUTHORIZED);
        }
        return response()->json(['message' => 'Plano encontrado com sucesso', 'data' => $plan], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plan $plan): JsonResponse
    {

        $request->validate([
            'title' => ['required', 'max:255'],
            'description' => ['sometimes'],
            'value' => ['required', 'numeric'],
        ]);

        if (!$request->user()->id == $plan->user_id) {
            return response()->json(['message' => 'O usuário não tem permissão para ver esse plano'], Response::HTTP_UNAUTHORIZED);
        }

        $plan->update([
            'title' => $request->title,
            'description' => $request->description,
            'value' => $request->value
        ]);

        $plan->save();

        return response()->json(['message' => 'Plano atualizado', 'data' => $plan], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Plan $plan): Response
    {
        if (!$request->user()->id === $plan->user_id) {
            return response()->json(['message' => 'O plano não pertence a esse usuário'], Response::HTTP_UNAUTHORIZED);
        }
        $plan->delete();

        return response()->noContent();
    }
}

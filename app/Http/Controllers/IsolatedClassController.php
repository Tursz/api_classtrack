<?php

namespace App\Http\Controllers;

use App\Models\IsolatedClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IsolatedClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $isolatedClasses = $request->user()->isolatedClasses()->latest('date')->get();

        return response()->json(['message' => 'Aulas avulsas encontradas!', 'data' => $isolatedClasses], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'max:255'],
            'date' => ['required', 'date'],
            'observation' => ['nullable'],
            'value' => ['numeric', 'nullable'],
        ], [
            'name.required' => 'O nome da aula avulsa é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'date.required' => 'A data da aula é obrigatória.',
            'date.date' => 'Informe uma data válida.',
            'value.numeric' => 'O valor deve ser um número.',
        ]);

        $isolatedClass = DB::transaction(function () use ($request) {

            $isolatedClass = IsolatedClass::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'date' => $request->date,
                'observation' => $request->observation,
                'value' => $request->value,
            ]);

            return $isolatedClass;
        });

        return response()->json(['message' => 'Aula avulsa criada com sucesso', 'data' => $isolatedClass], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, IsolatedClass $isolatedClass)
    {
        if (!$request->user()->id == $isolatedClass->user_id) {
            return response()->json(['message' => 'Usuário não tem acesso'], Response::HTTP_FORBIDDEN);
        }

        return response()->json(['message' => 'Aula avulsa encontrada', 'data' => $isolatedClass], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IsolatedClass $isolatedClass)
    {
        if (!$request->user()->id == $isolatedClass->user_id) {
            return response()->json(['message' => 'Usuário não tem acesso'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'name' => ['sometimes', 'max:255'],
            'date' => ['sometimes', 'date'],
            'observation' => ['nullable'],
            'value' => ['sometimes', 'numeric'],
        ], [
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'date.date' => 'Informe uma data válida.',
            'value.numeric' => 'O valor deve ser um número.',
        ]);

        $isolatedClass = DB::transaction(function () use ($request, $isolatedClass) {
            $isolatedClass->update([
                'name' => $request->name ?: $isolatedClass->name,
                'observation' => $request->observation ?: $isolatedClass->observation,
                'value' => $request->value ?: $isolatedClass->value,
                'date' => $request->date ?: $isolatedClass->date
            ]);
            return $isolatedClass;
        });

        return response()->json(['message' => 'Aula avulsa atualizada', 'data' => $isolatedClass], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, IsolatedClass $isolatedClass)
    {
        if (!$request->user()->id == $isolatedClass->user_id) {
            return response()->json(['message' => 'Usuário não tem acesso'], Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($isolatedClass) {
            $isolatedClass->delete();
        });

        return response()->json(['message' => 'Aula avulsa removida com sucesso'], Response::HTTP_OK);
    }
}

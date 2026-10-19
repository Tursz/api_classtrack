<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = $request->user()->groups()
            ->with('classrooms.learner')
            ->get()
            ->map(fn($group) => [
                'id' => $group->id,
                'title' => $group->title,
                'description' => $group->description,
                'learners' => $group->classrooms->map(fn($classroom) => $classroom->learner),
            ]);

        return response()->json(['message' => 'Grupos encontrados', 'data' => $groups], Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'max:255'],
            'description' => ['required'],
            'learner_ids' => ['sometimes', 'array'],
            'learner_ids.*' => ['exists:learners,id'],
        ], [
            'title.required' => 'O título do grupo é obrigatório.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'description.required' => 'A descrição do grupo é obrigatória.',
            'learner_ids.array' => 'A lista de alunos deve ser um array.',
            'learner_ids.*.exists' => 'Um ou mais alunos selecionados não foram encontrados.',
        ]);

        $group = Group::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($request->learner_ids ?? [] as $learnerId) {
            Classroom::create([
                'group_id' => $group->id,
                'learner_id' => $learnerId,
            ]);
        }

        $group->load('classrooms.learner');

        return response()->json(['message' => 'Grupo criado com sucesso', 'data' => $group], Response::HTTP_CREATED);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $group->load('classrooms.learner');

        return response()->json(['message' => 'Grupo encontrado', 'data' => $group], Response::HTTP_OK);
    }

    public function update(Request $request, Group $group): JsonResponse
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $request->validate([
            'title' => ['required', 'max:255'],
            'description' => ['required'],
            'learner_ids' => ['sometimes', 'array'],
            'learner_ids.*' => ['exists:learners,id'],
        ], [
            'title.required' => 'O título do grupo é obrigatório.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'description.required' => 'A descrição do grupo é obrigatória.',
            'learner_ids.array' => 'A lista de alunos deve ser um array.',
            'learner_ids.*.exists' => 'Um ou mais alunos selecionados não foram encontrados.',
        ]);

        $group->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->has('learner_ids')) {
            Classroom::where('group_id', $group->id)->delete();
            foreach ($request->learner_ids as $learnerId) {
                Classroom::create([
                    'group_id' => $group->id,
                    'learner_id' => $learnerId,
                ]);
            }
        }

        $group->load('classrooms.learner');

        return response()->json(['message' => 'Grupo atualizado com sucesso', 'data' => $group], Response::HTTP_OK);
    }

    public function destroy(Request $request, Group $group): JsonResponse
    {
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $group->delete();

        return response()->json(['message' => 'Grupo excluído com sucesso'], Response::HTTP_OK);
    }
}
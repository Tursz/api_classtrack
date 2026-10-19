<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupLesson;
use App\Services\ScheduleValidator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class GroupLessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groupIds = $request->user()->groups()->pluck('id');

        $lessons = GroupLesson::whereIn('group_id', $groupIds)
            ->with('group')
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();

        return response()->json(['message' => 'Aulas de grupo encontradas', 'data' => $lessons], Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'date' => ['required', 'date'],
            'start_at' => ['required'],
            'end_at' => ['required', 'after:start_at'],
        ], [
            'group_id.required' => 'O grupo é obrigatório.',
            'group_id.exists' => 'O grupo selecionado não foi encontrado.',
            'date.required' => 'A data da aula é obrigatória.',
            'date.date' => 'Informe uma data válida.',
            'start_at.required' => 'O horário de início é obrigatório.',
            'end_at.required' => 'O horário de término é obrigatório.',
            'end_at.after' => 'O horário de término deve ser posterior ao horário de início.',
        ]);

        $group = Group::findOrFail($request->group_id);
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        ScheduleValidator::assertNoConflict(
            $request->user()->id,
            $request->date,
            $request->start_at,
            $request->end_at
        );

        $lesson = GroupLesson::create([
            'group_id' => $request->group_id,
            'date' => $request->date,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
        ]);

        $lesson->load('group');

        return response()->json(['message' => 'Aula de grupo agendada com sucesso', 'data' => $lesson], Response::HTTP_CREATED);
    }

    public function update(Request $request, GroupLesson $groupLesson)
    {
        $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'date' => ['required', 'date'],
            'start_at' => ['required'],
            'end_at' => ['required', 'after:start_at'],
            'occurrences' => ['sometimes', 'integer', 'min:1', 'max:52'],
        ], [
            'group_id.required' => 'O grupo é obrigatório.',
            'group_id.exists' => 'O grupo selecionado não foi encontrado.',
            'date.required' => 'A data da aula é obrigatória.',
            'date.date' => 'Informe uma data válida.',
            'start_at.required' => 'O horário de início é obrigatório.',
            'end_at.required' => 'O horário de término é obrigatório.',
            'end_at.after' => 'O horário de término deve ser posterior ao horário de início.',
            'occurrences.integer' => 'A quantidade de ocorrências deve ser um número inteiro.',
            'occurrences.min' => 'A quantidade de ocorrências deve ser de pelo menos 1.',
            'occurrences.max' => 'A quantidade de ocorrências não pode ser maior que 52.',
        ]);

        $group = Group::findOrFail($request->group_id);
        if ($group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $occurrences = $request->integer('occurrences', 1);
        $baseDate = Carbon::parse($request->date);

        $dates = [];
        for ($i = 0; $i < $occurrences; $i++) {
            $dates[] = $baseDate->copy()->addWeeks($i)->format('Y-m-d');
        }

        foreach ($dates as $date) {
            ScheduleValidator::assertNoConflict(
                $request->user()->id,
                $date,
                $request->start_at,
                $request->end_at
            );
        }

        $lessons = DB::transaction(function () use ($dates, $request) {
            $created = [];
            foreach ($dates as $date) {
                $lesson = GroupLesson::create([
                    'group_id' => $request->group_id,
                    'date' => $date,
                    'start_at' => $request->start_at,
                    'end_at' => $request->end_at,
                ]);
                $lesson->load('group');
                $created[] = $lesson;
            }
            return $created;
        });

        return response()->json([
            'message' => count($lessons) > 1
                ? count($lessons) . ' aulas de grupo agendadas com sucesso'
                : 'Aula de grupo agendada com sucesso',
            'data' => $lessons,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, GroupLesson $groupLesson): JsonResponse
    {
        if ($groupLesson->group->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $groupLesson->delete();

        return response()->json(['message' => 'Aula de grupo excluída com sucesso'], Response::HTTP_OK);
    }
}
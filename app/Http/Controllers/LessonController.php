<?php

namespace App\Http\Controllers;

use App\Models\Learner;
use App\Models\Lesson;
use App\Services\ScheduleValidator;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LessonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $learnerIds = $request->user()->learners()->pluck('id');

        $lessons = Lesson::whereIn('learner_id', $learnerIds)
            ->with('learner')
            ->orderBy('date')
            ->orderBy('start_at')
            ->get();

        return response()->json(['message' => 'Aulas encontradas', 'data' => $lessons], Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'learner_id' => ['required', 'exists:learners,id'],
            'date' => ['required', 'date'],
            'start_at' => ['required'],
            'end_at' => ['required', 'after:start_at'],
            'occurrences' => ['sometimes', 'integer', 'min:1', 'max:52'],
        ]);

        $learner = Learner::findOrFail($request->learner_id);
        if ($learner->user_id !== $request->user()->id) {
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
                $lesson = Lesson::create([
                    'learner_id' => $request->learner_id,
                    'date' => $date,
                    'start_at' => $request->start_at,
                    'end_at' => $request->end_at,
                ]);
                $lesson->load('learner');
                $created[] = $lesson;
            }
            return $created;
        });

        return response()->json([
            'message' => count($lessons) > 1
                ? count($lessons) . ' aulas agendadas com sucesso'
                : 'Aula agendada com sucesso',
            'data' => $lessons,
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Lesson $lesson)
    {
        $request->validate([
            'learner_id' => ['required', 'exists:learners,id'],
            'date' => ['required', 'date'],
            'start_at' => ['required'],
            'end_at' => ['required', 'after:start_at'],
        ]);

        $learner = Learner::findOrFail($request->learner_id);
        if ($learner->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        ScheduleValidator::assertNoConflict(
            $request->user()->id,
            $request->date,
            $request->start_at,
            $request->end_at,
            ignoreLessonId: $lesson->id
        );

        $lesson->update([
            'learner_id' => $request->learner_id ?: $lesson->learner_id,
            'date' => $request->date ?: $lesson->date,
            'start_at' => $request->start_at ?: $lesson->start_at,
            'end_at' => $request->end_at ?: $lesson->end_at,
        ]);

        $lesson->load('learner');

        return response()->json(
            [
                'message' => 'Aula atualizada com sucesso',
                'data' => $lesson
            ],
            Response::HTTP_OK
        );
    }

    public function destroy(Request $request, Lesson $lesson): JsonResponse
    {
        if ($lesson->learner->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ação não autorizada'], Response::HTTP_UNAUTHORIZED);
        }

        $lesson->delete();

        return response()->json(['message' => 'Aula excluída com sucesso'], Response::HTTP_OK);
    }
}
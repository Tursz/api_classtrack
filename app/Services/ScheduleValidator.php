<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\GroupLesson;
use Illuminate\Validation\ValidationException;

class ScheduleValidator
{
    /**
     * Verifica se o professor já tem alguma aula (individual ou em grupo)
     * que conflita com o horário informado, na mesma data.
     */
    public static function assertNoConflict(
        int $userId,
        string $date,
        string $startAt,
        string $endAt,
        ?int $ignoreLessonId = null,
        ?int $ignoreGroupLessonId = null
    ): void {
    $lessonConflict = Lesson::whereHas('learner', fn ($q) => $q->where('user_id', $userId))
            ->where('date', $date)
            ->when($ignoreLessonId, fn ($q) => $q->where('id', '!=', $ignoreLessonId))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        $groupLessonConflict = GroupLesson::whereHas('group', fn ($q) => $q->where('user_id', $userId))
            ->where('date', $date)
            ->when($ignoreGroupLessonId, fn ($q) => $q->where('id', '!=', $ignoreGroupLessonId))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        if ($lessonConflict || $groupLessonConflict) {
            throw ValidationException::withMessages([
                'start_at' => ['Você já tem uma aula agendada nesse horário.'],
            ]);
        }
    }
}
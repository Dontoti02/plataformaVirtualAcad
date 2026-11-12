<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Models\Participant;

/**
 * Notas por indicador, por capacidad y final. Misma fórmula que la línea
 * actual (EvaluationHelper::updateScores):
 *
 * - indicador = promedio redondeado de sus tareas y evaluaciones calificadas;
 * - capacidad = promedio redondeado de sus indicadores con alguna calificada;
 * - final     = promedio redondeado de las capacidades con alguna calificada,
 *               usando la nota de recuperación en lugar de la de la capacidad
 *               cuando existe.
 *
 * Se recalcula a la persona entera dentro de la clase, no respuesta a
 * respuesta: así no depende de filas creadas de antemano y una respuesta de
 * un contenido sin indicador simplemente no cuenta.
 */
class CapacityScoreRepository
{
    public static function recalculate(int $classroomId, int $personId): float
    {
        $now = now();

        $answers = DB::table('answer')
            ->join('content', 'answer.content_id', '=', 'content.id')
            ->join('content_group', 'content.content_group_id', '=', 'content_group.id')
            ->join('indicator', 'content.indicator_id', '=', 'indicator.id')
            ->join('capacity', 'indicator.capacity_id', '=', 'capacity.id')
            ->where('content_group.classroom_id', $classroomId)
            ->where('capacity.classroom_id', $classroomId)
            ->where('answer.person_id', $personId)
            ->where('answer.status', AnswerStatus::EVALUATED)
            ->whereIn('content.type', ['task', 'evaluation'])
            ->get([
                'answer.score',
                'content.indicator_id',
                'indicator.capacity_id',
                'capacity.weight',
            ]);

        $indicatorScores = $answers
            ->groupBy('indicator_id')
            ->map(fn ($items) => self::round($items->avg('score')));

        foreach ($indicatorScores as $indicatorId => $score) {
            DB::table('student_indicator')->upsert(
                [[
                    'person_id' => $personId,
                    'indicator_id' => $indicatorId,
                    'score' => $score,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['person_id', 'indicator_id'],
                ['score', 'updated_at'],
            );
        }

        $capacityOf = $answers->pluck('capacity_id', 'indicator_id');
        $weightOf = $answers->pluck('weight', 'capacity_id');

        $capacityScores = $indicatorScores
            ->groupBy(fn ($score, $indicatorId) => $capacityOf[$indicatorId], true)
            ->map(fn ($scores) => self::round($scores->avg()));

        foreach ($capacityScores as $capacityId => $score) {
            $weight = $weightOf[$capacityId];

            DB::table('student_capacity')->upsert(
                [[
                    'person_id' => $personId,
                    'capacity_id' => $capacityId,
                    'score' => $score,
                    'score_by_weight' => $weight ? $score * $weight : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['person_id', 'capacity_id'],
                ['score', 'score_by_weight', 'updated_at'],
            );
        }

        self::removeStale($classroomId, $personId, $indicatorScores->keys()->all(), $capacityScores->keys()->all());

        $participant = Participant::query()
            ->where('classroom_id', $classroomId)
            ->where('person_id', $personId)
            ->first();

        // Sin ninguna nota por capacidad no hay nada que promediar: la nota
        // final que tenga (importada o de antes de las capacidades) se respeta.
        if ($capacityScores->isEmpty()) {
            return (float) ($participant?->score ?? 0);
        }

        $final = DB::table('student_capacity')
            ->where('person_id', $personId)
            ->whereIn('capacity_id', $capacityScores->keys())
            ->avg(DB::raw('COALESCE(recover_score, score)'));

        $final = self::round($final);

        $participant?->update(['score' => $final]);

        return (float) $final;
    }

    /**
     * Notas de indicadores y capacidades de la clase que ya no tienen ninguna
     * calificación (p. ej. su contenido pasó a otro indicador). Una capacidad
     * con nota de recuperación se conserva: esa nota la puso alguien a mano.
     */
    private static function removeStale(int $classroomId, int $personId, array $indicatorIds, array $capacityIds): void
    {
        DB::table('student_indicator')
            ->where('person_id', $personId)
            ->whereIn('indicator_id', DB::table('indicator')
                ->join('capacity', 'indicator.capacity_id', '=', 'capacity.id')
                ->where('capacity.classroom_id', $classroomId)
                ->select('indicator.id'))
            ->whereNotIn('indicator_id', $indicatorIds)
            ->delete();

        DB::table('student_capacity')
            ->where('person_id', $personId)
            ->whereIn('capacity_id', DB::table('capacity')
                ->where('classroom_id', $classroomId)
                ->select('id'))
            ->whereNotIn('capacity_id', $capacityIds)
            ->whereNull('recover_score')
            ->delete();
    }

    public static function round($value): int
    {
        return $value === null ? 0 : (int) round($value);
    }
}

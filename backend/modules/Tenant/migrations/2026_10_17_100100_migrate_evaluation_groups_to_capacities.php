<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Competencias → capacidades, sin perder nada y repetible.
 *
 * Por cada competencia (evaluation_group) vigente:
 * - una capacidad con su nombre y su peso, marcada con evaluation_group_id;
 * - un indicador «Indicador general»;
 * - sus contenidos pasan a ese indicador.
 * Después, las notas por indicador y capacidad se calculan de las respuestas
 * ya calificadas, con la misma fórmula que el cálculo en vivo
 * (EvaluationScoreService).
 *
 * Lo que NO hace, por decisión: tocar participant.score. Las notas finales ya
 * puestas se conservan; la de un estudiante solo cambia cuando el docente
 * vuelve a calificar en esa clase. Tampoco borra evaluation_group, average ni
 * average_detail.
 *
 * Repetible: la marca evaluation_group_id evita duplicar capacidades, solo se
 * asignan contenidos sin indicador y las notas se escriben con upsert sobre
 * claves únicas.
 */
return new class extends Migration
{
    private const INDICATOR_NAME = 'Indicador general';

    public function up(): void
    {
        $now = now();

        $groups = DB::table('evaluation_group')
            ->whereNull('deleted_at')
            ->orderBy('classroom_id')
            ->orderBy('id')
            ->get();

        foreach ($groups as $group) {
            $capacityId = DB::table('capacity')->where('evaluation_group_id', $group->id)->value('id');

            if (! $capacityId) {
                $order = (int) DB::table('capacity')->where('classroom_id', $group->classroom_id)->max('order');

                $capacityId = DB::table('capacity')->insertGetId([
                    'classroom_id' => $group->classroom_id,
                    'evaluation_group_id' => $group->id,
                    'name' => $group->title,
                    'order' => $order + 1,
                    'weight' => $group->weight,
                    'created_at' => $group->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }

            $indicatorId = DB::table('indicator')
                ->where('capacity_id', $capacityId)
                ->whereNull('deleted_at')
                ->orderBy('order')
                ->value('id');

            if (! $indicatorId) {
                $indicatorId = DB::table('indicator')->insertGetId([
                    'capacity_id' => $capacityId,
                    'name' => self::INDICATOR_NAME,
                    'order' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('content')
                ->where('evaluation_group_id', $group->id)
                ->whereNull('indicator_id')
                ->update(['indicator_id' => $indicatorId]);
        }

        $this->calculateScores($now);
    }

    /**
     * Nota por indicador = promedio redondeado de sus respuestas calificadas.
     * Nota por capacidad = promedio redondeado de sus indicadores.
     */
    private function calculateScores($now): void
    {
        $answers = DB::table('answer')
            ->join('content', 'answer.content_id', '=', 'content.id')
            ->join('indicator', 'content.indicator_id', '=', 'indicator.id')
            ->join('capacity', 'indicator.capacity_id', '=', 'capacity.id')
            ->whereNull('answer.deleted_at')
            ->whereNull('content.deleted_at')
            ->whereNull('indicator.deleted_at')
            ->whereNull('capacity.deleted_at')
            ->whereIn('content.type', ['task', 'evaluation'])
            ->where('answer.status', 'evaluated')
            ->get([
                'answer.person_id',
                'answer.score',
                'content.indicator_id',
                'indicator.capacity_id',
                'capacity.weight',
            ]);

        $round = fn ($value) => $value === null ? 0 : (int) round($value);

        $indicators = $answers
            ->groupBy(fn ($answer) => $answer->person_id.'-'.$answer->indicator_id)
            ->map(fn ($items) => [
                'person_id' => $items->first()->person_id,
                'indicator_id' => $items->first()->indicator_id,
                'capacity_id' => $items->first()->capacity_id,
                'weight' => $items->first()->weight,
                'score' => $round($items->avg('score')),
            ])
            ->values();

        foreach ($indicators->chunk(500) as $chunk) {
            DB::table('student_indicator')->upsert(
                $chunk->map(fn ($item) => [
                    'person_id' => $item['person_id'],
                    'indicator_id' => $item['indicator_id'],
                    'score' => $item['score'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
                ['person_id', 'indicator_id'],
                ['score', 'updated_at'],
            );
        }

        $capacities = $indicators
            ->groupBy(fn ($item) => $item['person_id'].'-'.$item['capacity_id'])
            ->map(function ($items) use ($round) {
                $first = $items->first();
                $score = $round($items->avg('score'));

                return [
                    'person_id' => $first['person_id'],
                    'capacity_id' => $first['capacity_id'],
                    'score' => $score,
                    'score_by_weight' => $first['weight'] ? $score * $first['weight'] : null,
                ];
            })
            ->values();

        foreach ($capacities->chunk(500) as $chunk) {
            DB::table('student_capacity')->upsert(
                $chunk->map(fn ($item) => $item + ['created_at' => $now, 'updated_at' => $now])->all(),
                ['person_id', 'capacity_id'],
                ['score', 'score_by_weight', 'updated_at'],
            );
        }
    }

    public function down(): void
    {
        // Las capacidades salidas de competencias se quitan; las creadas a mano
        // después, no. Los contenidos vuelven a quedar sin indicador.
        $capacityIds = DB::table('capacity')->whereNotNull('evaluation_group_id')->pluck('id');
        $indicatorIds = DB::table('indicator')->whereIn('capacity_id', $capacityIds)->pluck('id');

        DB::table('content')->whereIn('indicator_id', $indicatorIds)->update(['indicator_id' => null]);
        DB::table('capacity')->whereIn('id', $capacityIds)->delete();
    }
};

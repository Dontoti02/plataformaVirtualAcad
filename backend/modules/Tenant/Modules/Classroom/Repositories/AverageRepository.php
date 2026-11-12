<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Carbon\Carbon;
use Modules\Tenant\Modules\Classroom\Models\Average;
use Modules\Tenant\Modules\Classroom\Models\AverageDetail;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\EvaluationGroup;
use Modules\Tenant\Modules\Classroom\Models\Participant;

class AverageRepository
{
    public static function list(int $classroom_id, int $person_id, int $content_group_id)
    {
        $evaluation_groups = EvaluationGroup::select('id', 'title', 'weight')
            ->where('classroom_id', $classroom_id)
            ->get()
            ->map(function ($item) use ($person_id, $content_group_id) {

                $average_details = $item->averageDetails()
                    ->where('person_id', $person_id)
                    ->get();

                $count = $average_details
                    ->where('content_group_id', $content_group_id)
                    ->count();

                $sum = $average_details
                    ->where('content_group_id', $content_group_id)
                    ->sum('score');

                $aux = $average_details->count();

                $score = ($aux > 0 && $count > 0)
                    ? ($sum / $count) * ($item->weight / $aux)
                    : 0;

                $score = round($score, 2);

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'weight' => (float) $item->weight,
                    'score' => $score,
                ];
            });

        return $evaluation_groups;
    }

    public static function getFinalNote(int $classroom_id, int $person_id)
    {
        $participant = Participant::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->first();

        $result = (float) $participant->score;

        return $result;
    }

    public static function calculateAverageDetail(int $classroom_id, int $person_id)
    {
        $contents = Content::selectRaw("
            content.content_group_id,
            content.evaluation_group_id,
            COUNT(answer.id) as count,
            SUM(answer.score) as sum
        ")
            ->join('answer', 'content.id', 'answer.content_id')
            ->join('evaluation_group', 'content.evaluation_group_id', 'evaluation_group.id')
            ->whereIn('content.type', ['task', 'evaluation'])
            ->where('answer.person_id', $person_id)
            ->where('evaluation_group.classroom_id', $classroom_id)
            ->groupBy('content.content_group_id', 'content.evaluation_group_id')
            ->get()
            ->map(function ($item) {
                $score = $item->count > 0
                    ? ($item->sum / $item->count)
                    : 0;

                $score = round($score, 2);

                return [
                    'content_group_id' => $item->content_group_id,
                    'evaluation_group_id' => $item->evaluation_group_id,
                    'score' => $score,
                ];
            });

        foreach ($contents as $content) {
            $average_detail = AverageDetail::select()
                ->where('person_id', $person_id)
                ->where('content_group_id', $content['content_group_id'])
                ->where('evaluation_group_id', $content['evaluation_group_id'])
                ->first();

            $average_detail->update([
                'score' => $content['score'],
            ]);
        }

        self::calculateAverage($classroom_id, $person_id);
    }

    public static function calculateAverage(int $classroom_id, int $person_id)
    {
        $evaluation_groups = EvaluationGroup::selectRaw("
            evaluation_group.id,
            evaluation_group.weight,
            COUNT(average_detail.id) as count,
            SUM(average_detail.score) as sum
        ")
            ->join('average_detail', 'evaluation_group.id', 'average_detail.evaluation_group_id')
            ->where('average_detail.person_id', $person_id)
            ->where('evaluation_group.classroom_id', $classroom_id)
            ->groupBy('evaluation_group.id', 'evaluation_group.title', 'evaluation_group.weight')
            ->get()
            ->map(function ($item) {
                $score = $item->count > 0
                    ? ($item->sum / $item->count) * $item->weight
                    : 0;

                $score = round($score, 2);

                return [
                    'id' => $item->id,
                    'score' => $score,
                ];
            });

        foreach ($evaluation_groups as $evaluation_group) {
            $average = Average::select()
                ->where('person_id', $person_id)
                ->where('evaluation_group_id', $evaluation_group['id'])
                ->first();

            $average->update([
                'score' => $evaluation_group['score'],
            ]);
        }

        self::calculateFinalNote($classroom_id, $person_id);
    }

    public static function calculateFinalNote(int $classroom_id, int $person_id)
    {
        $averages = Average::select([
            'average.id',
            'average.score',
        ])
            ->join('evaluation_group', 'average.evaluation_group_id', 'evaluation_group.id')
            ->where('person_id', $person_id)
            ->where('evaluation_group.classroom_id', $classroom_id)
            ->get();

        $score = $averages->sum('score');
        $score = round($score, 2);

        $participant = Participant::select()
            ->where('person_id', $person_id)
            ->where('classroom_id', $classroom_id)
            ->first();

        $participant->update([
            'score' => $score,
        ]);
    }
}

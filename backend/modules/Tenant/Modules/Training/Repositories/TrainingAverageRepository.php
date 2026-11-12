<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Modules\Tenant\Modules\Training\Models\TrainingAverage;
use Modules\Tenant\Modules\Training\Models\TrainingAverageDetail;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingEvaluationGroup;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;

class TrainingAverageRepository
{
    public static function list(int $training_id, int $person_id, int $training_content_group_id)
    {
        $evaluation_groups = TrainingEvaluationGroup::select('id', 'title', 'weight')
            ->where('training_id', $training_id)
            ->get()
            ->map(function ($item) use ($person_id, $training_content_group_id) {

                $average_details = $item->averageDetails()
                    ->where('person_id', $person_id)
                    ->get();

                $count = $average_details
                    ->where('training_content_group_id', $training_content_group_id)
                    ->count();

                $sum = $average_details
                    ->where('training_content_group_id', $training_content_group_id)
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

    public static function getFinalNote(int $training_id, int $person_id)
    {
        $participant = TrainingParticipant::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->first();

        $result = (float) $participant->score;

        return $result;
    }

    public static function calculateAverageDetail(int $training_id, int $person_id)
    {
        $contents = TrainingContent::selectRaw("
            training_content.training_content_group_id,
            training_content.training_evaluation_group_id,
            COUNT(training_answer.id) as count,
            SUM(training_answer.score) as sum
        ")
            ->join('training_answer', 'training_content.id', 'training_answer.training_content_id')
            ->join('training_evaluation_group', 'training_content.training_evaluation_group_id', 'training_evaluation_group.id')
            ->whereIn('training_content.type', ['task', 'evaluation'])
            ->where('training_answer.person_id', $person_id)
            ->where('training_evaluation_group.training_id', $training_id)
            ->groupBy('training_content.training_content_group_id', 'training_content.training_evaluation_group_id')
            ->get()
            ->map(function ($item) {
                $score = $item->count > 0
                    ? ($item->sum / $item->count)
                    : 0;

                $score = round($score, 2);

                return [
                    'training_content_group_id' => $item->training_content_group_id,
                    'training_evaluation_group_id' => $item->training_evaluation_group_id,
                    'score' => $score,
                ];
            });

        foreach ($contents as $content) {
            $average_detail = TrainingAverageDetail::select()
                ->where('person_id', $person_id)
                ->where('training_content_group_id', $content['training_content_group_id'])
                ->where('training_evaluation_group_id', $content['training_evaluation_group_id'])
                ->first();

            $average_detail->update([
                'score' => $content['score'],
            ]);
        }

        self::calculateAverage($training_id, $person_id);
    }

    public static function calculateAverage(int $training_id, int $person_id)
    {
        $evaluation_groups = TrainingEvaluationGroup::selectRaw("
            training_evaluation_group.id,
            training_evaluation_group.weight,
            COUNT(training_average_detail.id) as count,
            SUM(training_average_detail.score) as sum
        ")
            ->join('training_average_detail', 'training_evaluation_group.id', 'training_average_detail.training_evaluation_group_id')
            ->where('training_average_detail.person_id', $person_id)
            ->where('training_evaluation_group.training_id', $training_id)
            ->groupBy('training_evaluation_group.id', 'training_evaluation_group.title', 'training_evaluation_group.weight')
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
            $average = TrainingAverage::select()
                ->where('person_id', $person_id)
                ->where('training_evaluation_group_id', $evaluation_group['id'])
                ->first();

            $average->update([
                'score' => $evaluation_group['score'],
            ]);
        }

        self::calculateFinalNote($training_id, $person_id);
    }

    public static function calculateFinalNote(int $training_id, int $person_id)
    {
        $averages = TrainingAverage::select([
            'training_average.id',
            'training_average.score',
        ])
            ->join('training_evaluation_group', 'training_average.training_evaluation_group_id', 'training_evaluation_group.id')
            ->where('person_id', $person_id)
            ->where('training_evaluation_group.training_id', $training_id)
            ->get();

        $score = $averages->sum('score');
        $score = round($score, 2);

        $participant = TrainingParticipant::select()
            ->where('person_id', $person_id)
            ->where('training_id', $training_id)
            ->first();

        $participant->update([
            'score' => $score,
        ]);
    }
}

<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Modules\Training\Helpers\TrainingEvaluationHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingAnswer;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroupParticipant;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingEvaluationRepository
{
    public static function list(int $training_id, int $person_id)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::TRAINING_STUDENT;

        $training = Training::byKey('id', $training_id);

        if (!$is_student) {
            if ($person_id === 0) {
                throw new Exception('El parámetro person_id es requerido');
            }

            $user = User::byKey('person_id', $person_id);
        }

        $participant = TrainingHelper::validateStudentAccess($user->person_id, $training_id, $is_student);

        $final_note = (float) $participant->score;

        $status = $final_note >= 11 ? 'Aprobado' : 'No aprobado';

        $next_evaluation = $training->contentGroups()
            ->select([
                'training_content.id',
                'training_content.title',
                'training_content.date_start',
                'training_content.date_limit',
            ])
            ->join('training_content', 'training_content_group.id', 'training_content.training_content_group_id')
            ->whereIn('training_content.type', ['task', 'evaluation'])
            ->where('training_content.date_start', '>=', Carbon::now())
            ->orderBy('training_content.date_start', 'asc')
            ->first();

        $content_groups = $training->contentGroups()
            ->select('id', 'title')
            ->whereNot('title', 'Sílabo')
            ->get()
            ->each(function ($content_group) use ($user, $training) {
                $content_group->evaluations = $content_group->contents()
                    ->select([
                        'training_content.id',
                        'training_content.title',
                        'training_content.type',
                        'training_content.created_at as date',
                        'training_content.date_start',
                        'training_content.date_limit',
                        'training_evaluation_group.title as evaluation_group',
                        'training_answer.score',
                    ])
                    ->join('training_evaluation_group', 'training_content.training_evaluation_group_id', 'training_evaluation_group.id')
                    ->join('training_answer', 'training_content.id', 'training_answer.training_content_id')
                    ->whereIn('training_content.type', ['task', 'evaluation'])
                    ->where('training_answer.person_id', $user->person_id)
                    ->get()
                    ->each(function ($evaluation) {
                        $evaluation->score = (float) $evaluation->score;
                    });

                $content_group->evaluation_groups = TrainingAverageRepository::list($training->id, $user->person_id, $content_group->id);
            });

        $result = [
            'status' => $status,
            'final_note' => $final_note,
            'next_evaluation' => $next_evaluation,
            'content_groups' => $content_groups,
        ];

        return $result;
    }

    public static function evaluate(Request $request)
    {
        $user = User::authenticated();

        TrainingEvaluationHelper::validateRequest($request);

        $answerId = $request->input('training_answer_id');
        $score = $request->input('score');

        $answer = TrainingAnswer::findOrFail($answerId);
        $content = $answer->content;
        $training = $content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        if ($content->date_limit && Carbon::now() < $content->date_limit) {
            throw new Exception('La tarea o evaluación aún no ha finalizado');
        }

        $content->update([
            'is_open' => false,
        ]);

        if ($score > $content->score) {
            throw new Exception('La nota no puede ser mayor a la establecida en la tarea o evaluación');
        }

        $answersId = [$answer->id];
        $personsId = [$answer->person_id];

        if ($content->is_group_task) {
            $taskGroupsId = $content->taskGroups()->pluck('id')->toArray();

            $participant = TrainingParticipant::select()
                ->where('training_id', $training->id)
                ->where('person_id', $answer->person_id)
                ->first();

            $groupParticipant = TrainingTaskGroupParticipant::select()
                ->whereIn('training_task_group_id', $taskGroupsId)
                ->where('training_participant_id', $participant->id)
                ->first();

            $groupParticipants = TrainingTaskGroupParticipant::select()
                ->where('training_task_group_id', $groupParticipant->training_task_group_id)
                ->get();

            $participants = TrainingParticipant::select()
                ->whereIn('id', $groupParticipants->pluck('training_participant_id')->toArray())
                ->get();

            $personsId = $participants->pluck('person_id')->toArray();

            $answers = TrainingAnswer::select()
                ->whereIn('person_id', $personsId)
                ->where('training_content_id', $content->id)
                ->get();

            $answersId = $answers->pluck('id')->toArray();
        }

        TrainingAnswer::select()
            ->whereIn('id', $answersId)
            ->update([
                'status' => TrainingAnswerStatusEnum::EVALUATED,
                'score' => $score,
            ]);

        foreach ($personsId as $personId) {
            TrainingAverageRepository::calculateAverageDetail($training->id, $personId);
        }

        $result = [
            'final_note' => TrainingAverageRepository::getFinalNote($training->id, $answer->person_id),
        ];

        return $result;
    }

    public static function finalNotes(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingEvaluationHelper::validateFinalNotesRequest($request);
        TrainingHelper::validateASTAccess($user->person_id, $user->rol_id, $request->training_id);

        $result = TrainingParticipant::selectRaw("
                person.id,
                person.document_number,
                person.names,
                CAST(training_participant.score AS FLOAT) as score
           ")
            ->join('person', 'training_participant.person_id', 'person.id')
            ->where('training_participant.training_id', $request->training_id)
            ->orderBy('person.names', 'asc')
            ->get();

        foreach ($result as $participant) {
            $names = explode(' ', $participant->names);
            $firstTwo = implode(' ', array_slice($names, 0, 2));
            $rest = implode(' ', array_slice($names, 2));
            $rest = ucwords(strtolower($rest));
            $participant->names = "$firstTwo, $rest";
        }

        return $result;
    }
}

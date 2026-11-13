<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\EvaluationHelper;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Capacity;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class EvaluationRepository
{
    public static function list(int $classroom_id, int $person_id)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::STUDENT;

        \Modules\Tenant\Modules\Management\Helpers\StudyProgramHelper::validateClassroomAccess($classroom_id, $user);

        $classroom = Classroom::byKey('id', $classroom_id);

        if (!$is_student) {
            if ($person_id === 0) {
                throw new Exception('El parámetro person_id es requerido');
            }

            $user = User::byKey('person_id', $person_id);
        }

        $participant = ClassroomHelper::validateStudentAccess($user->person_id, $classroom_id, $is_student);

        $final_note = (float) $participant->score;

        $status = $final_note >= 11 ? 'Aprobado' : 'No aprobado';

        $next_evaluation = $classroom->contentGroups()
            ->select([
                'content.id',
                'content.title',
                'content.date_start',
                'content.date_limit',
            ])
            ->join('content', 'content_group.id', 'content.content_group_id')
            ->whereIn('content.type', ['task', 'evaluation'])
            ->where('content.date_start', '>=', Carbon::now())
            ->orderBy('content.date_start', 'asc')
            ->first();

        $capacities = Capacity::query()
            ->leftJoin('student_capacity', function ($join) use ($user) {
                $join->on('capacity.id', 'student_capacity.capacity_id')
                    ->where('student_capacity.person_id', $user->person_id);
            })
            ->where('capacity.classroom_id', $classroom->id)
            ->orderBy('capacity.order')
            ->get([
                'capacity.id',
                'capacity.name as title',
                'capacity.weight',
                'student_capacity.score',
                'student_capacity.recover_score',
            ])
            ->map(fn ($capacity) => [
                'id' => $capacity->id,
                'title' => $capacity->title,
                'weight' => (float) $capacity->weight,
                'score' => (float) ($capacity->recover_score ?? $capacity->score ?? 0),
            ]);

        $content_groups = $classroom->contentGroups()
            ->select('id', 'title')
            ->whereNotIn('title', ['Documentos Pedagógicos', 'Sílabo'])
            ->get()
            ->each(function ($content_group) use ($user, $capacities) {
                $content_group->evaluations = $content_group->contents()
                    ->select([
                        'content.id',
                        'content.title',
                        'content.type',
                        'content.created_at as date',
                        'content.date_start',
                        'content.date_limit',
                        'capacity.name as evaluation_group',
                        'capacity.name as capacity_name',
                        'indicator.name as indicator_name',
                        'answer.score',
                    ])
                    ->join('indicator', 'content.indicator_id', 'indicator.id')
                    ->join('capacity', 'indicator.capacity_id', 'capacity.id')
                    ->join('answer', 'content.id', 'answer.content_id')
                    ->whereIn('content.type', ['task', 'evaluation'])
                    ->where('answer.person_id', $user->person_id)
                    ->get()
                    ->each(function ($evaluation) {
                        $evaluation->score = (float) $evaluation->score;
                    });

                // Antes, la nota por competencia de esta unidad. Ahora la nota de
                // cada capacidad no se divide por unidades: es la misma en todas.
                $content_group->evaluation_groups = $capacities;
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
        $session = SessionManager::get();
        $user = User::find($session->id);

        EvaluationHelper::validateRequest($request);

        $answer = Answer::find($request->answer_id);
        $content = $answer->content;

        $classroom_id = $content->contentGroup->classroom_id;
        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

        if ($content->date_limit && Carbon::now() < $content->date_limit) {
            throw new Exception('La tarea o evaluación aún no ha finalizado');
        }

        $content->update([
            'is_open' => false,
        ]);

        if ($request->score > $content->score) {
            throw new Exception('La nota no puede ser mayor a la establecida en la tarea o evaluación');
        }

        $answer->update([
            'status' => AnswerStatus::EVALUATED,
            'score' => $request->score,
        ]);

        $result = [
            'final_note' => CapacityScoreRepository::recalculate($classroom_id, $answer->person_id),
        ];

        return $result;
    }

    public static function finalNotes(Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        EvaluationHelper::validateFinalNotesRequest($request);
        ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $request->classroom_id);

        $result = Participant::selectRaw("
            person.id,
            person.document_number,
            person.names,
            CAST(participant.score AS FLOAT) as score
       ")
            ->join('person', 'participant.person_id', 'person.id')
            ->where('participant.classroom_id', $request->classroom_id)
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

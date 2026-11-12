<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\StudentCapacity;
use Modules\Tenant\Modules\Classroom\Models\Teacher;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class ParticipantRepository
{
    public static function list(int $classroom_id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_teacher = $user->rol_id === RolTenant::TEACHER;

        $classroom = Classroom::byKey('id', $classroom_id);

        if ($is_teacher) {

            // Una columna por capacidad. Se siguen llamando evaluationGroups y
            // evaluation_<id> porque es lo que lee la pantalla.
            $evaluationGroups = $classroom->capacities()
                ->orderBy('order')
                ->get(['id', 'name as title', 'weight']);

            $result = Participant::selectRaw("
                participant.id,
                participant.person_id,
                person.names,
                cycle.name as cycle,
                CAST(participant.score AS FLOAT) as score,
                (
                    SELECT COUNT(*) 
                    FROM assistance
                    WHERE 
                        person_id = participant.person_id
                        AND classroom_id = participant.classroom_id
                        AND status = 'absence'
                ) as absences
            ")
                ->join('person', 'participant.person_id', '=', 'person.id')
                ->join('classroom', 'participant.classroom_id', '=', 'classroom.id')
                ->join('course', 'classroom.course_id', '=', 'course.id')
                ->join('cycle', 'course.cycle_id', '=', 'cycle.id')
                ->where('participant.classroom_id', $classroom_id)
                ->orderBy('person.names', 'asc')
                ->get();
 
            $participantIds = $result->pluck('person_id');

            $scores = StudentCapacity::whereIn('person_id', $participantIds)
                ->whereIn('capacity_id', $evaluationGroups->pluck('id'))
                ->get(['person_id', 'capacity_id', 'score', 'recover_score']);

            $details = [];
            foreach ($scores as $score) {
                // La recuperación, si la hay, es la nota que cuenta.
                $details[$score->person_id][$score->capacity_id] = $score->recover_score ?? $score->score;
            }

            $result->transform(function ($participant) use ($details, $evaluationGroups) {
                $participantDetails = isset($details[$participant->person_id]) ? $details[$participant->person_id] : [];

                $completeDetails = [];
                foreach ($evaluationGroups as $group) {
                    // $completeDetails[$group->id] = $participantDetails[$group->id] ?? 0;
                    $score = $participantDetails[$group->id] ?? 0;
                    $participant['evaluation_' . $group->id] = $score;
                }

                // $participant->scorePerEvaluationGroup = $completeDetails;
                return $participant;
            });


            return [
                'result' => $result,
                'evaluationGroups' => $evaluationGroups
            ];
        }

        $teachers = Teacher::select([
            'teacher.id',
            'teacher.person_id',
            'person.names',
            'person.email',
            'user.last_login',
        ])
            ->join('person', 'teacher.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('teacher.classroom_id', $classroom_id)
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = Participant::select([
            'participant.id',
            'participant.person_id',
            'person.names',
            'person.email',
            'user.last_login',
        ])
            ->join('person', 'participant.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('participant.classroom_id', $classroom_id)
            ->orderBy('person.names', 'asc')
            ->get();

        $result = [
            'teachers' => $teachers,
            'participants' => $participants,
        ];

        return $result;
    }
}

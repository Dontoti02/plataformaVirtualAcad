<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingAverage;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTeacher;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingParticipantRepository
{
    public static function list(int $training_id)
    {
        $roles = [
            RolTenant::TRAINING_ADMINISTRADOR,
            RolTenant::TRAINING_TEACHER,
            RolTenant::TRAINING_STUDENT,
        ];
        $user = User::authenticated($roles);
        $isTeacher = $user->rol_id === RolTenant::TRAINING_TEACHER;

        $training = Training::findOrFail($training_id);

        if ($isTeacher) {
            $evaluationGroups = $training->evaluationGroups()
                ->select('id', 'title', 'weight')
                ->get();

            $result = TrainingParticipant::selectRaw("
                training_participant.id,
                training_participant.person_id,
                person.names,
                CAST(training_participant.score AS FLOAT) as score,
                (
                    SELECT COUNT(*) 
                    FROM training_assistance
                    WHERE 
                        person_id = training_participant.person_id
                        AND training_id = training_participant.training_id
                        AND status = 'absence'
                ) as absences
            ")
                ->join('person', 'training_participant.person_id', '=', 'person.id')
                ->join('training', 'training_participant.training_id', '=', 'training.id')
                ->where('training_participant.training_id', $training_id)
                ->orderBy('person.names', 'asc')
                ->get();

            $participantIds = $result->pluck('person_id');

            $averages = TrainingAverage::whereIn('person_id', $participantIds)
                ->select('person_id', 'training_evaluation_group_id', 'score')
                ->get();

            $details = [];
            foreach ($averages as $average) {
                if (!isset($details[$average->person_id])) {
                    $details[$average->person_id] = [];
                }
                $details[$average->person_id][$average->training_evaluation_group_id] = $average->score;
            }

            $result->transform(function ($participant) use ($details, $evaluationGroups) {
                $participantDetails = isset($details[$participant->person_id]) ? $details[$participant->person_id] : [];

                foreach ($evaluationGroups as $group) {
                    $score = $participantDetails[$group->id] ?? 0;
                    $participant['evaluation_' . $group->id] = $score;
                }

                return $participant;
            });

            return [
                'result' => $result,
                'evaluationGroups' => $evaluationGroups
            ];
        }

        $teachers = TrainingTeacher::select([
            'training_teacher.id',
            'training_teacher.person_id',
            'person.names',
            'person.email',
            'user.last_login',
        ])
            ->join('person', 'training_teacher.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('training_teacher.training_id', $training_id)
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = TrainingParticipant::select([
            'training_participant.id',
            'training_participant.person_id',
            'person.names',
            'person.email',
            'user.last_login',
        ])
            ->join('person', 'training_participant.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('training_participant.training_id', $training_id)
            ->orderBy('person.names', 'asc')
            ->get();

        $result = [
            'teachers' => $teachers,
            'participants' => $participants,
        ];

        return $result;
    }
}

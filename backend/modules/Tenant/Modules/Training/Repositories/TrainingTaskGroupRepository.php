<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingTaskGroupHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroup;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroupParticipant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingTaskGroupRepository
{
    public static function list(int $training_content_id)
    {
        $content = TrainingContent::findOrFail($training_content_id);
        $training = $content->contentGroup->training;

        $participantsIncluded = [];

        $groups = $content->taskGroups()
            ->with('participants.participant.person')
            ->get();

        $groupsMap = [];
        foreach ($groups as $group) {
            $participantsMap = [];
            foreach ($group->participants as $participant) {
                $participantsMap[] = [
                    'id' => $participant->id,
                    'name' => $participant->participant->person->names,
                    'training_participant_id' => $participant->participant->id,
                ];

                $participantsIncluded[] = $participant->participant->id;
            }

            $groupsMap[] = [
                'id' => $group->id,
                'name' => $group->name,
                'participants' => $participantsMap,
            ];
        }

        $participants = $training->participants()
            ->with('person')
            ->whereNotIn('id', $participantsIncluded)
            ->where('is_active', true)
            ->get();

        $participantsMap = [];
        foreach ($participants as $participant) {
            $participantsMap[] = [
                'id' => $participant->id,
                'name' => $participant->person->names,
            ];
        }

        $result = [
            'participants' => $participantsMap,
            'groups' => $groupsMap,
        ];

        return $result;
    }

    public static function set(Request $request)
    {
        $user = User::authenticated();

        TrainingTaskGroupHelper::validateSetRequest($request);

        $id = $request->input('id');
        $contentId = $request->input('training_content_id');
        $name = $request->input('name');

        $content = TrainingContent::findOrFail($contentId);
        $training = $content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        if (!$content->is_group_task) {
            throw new Exception('El contenido no admite grupos.');
        }

        $exists = TrainingTaskGroup::select()
            ->where('id', '!=', $id)
            ->where('training_content_id', $contentId)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            throw new Exception('Ya existe un grupo con el mismo nombre.');
        }

        if ($id) {
            $group = TrainingTaskGroup::findOrFail($id);

            if ($group->score !== null && $group->score >= 0) {
                throw new Exception('No se puede modificar el grupo porque que ya ha registrado respuesta.');
            }

            $group->update([
                'person_task_register_id' => $user->person_id,
                'name' => $name,
            ]);
        } else {
            $group = TrainingTaskGroup::create([
                'training_id' => $training->id,
                'training_content_id' => $content->id,
                'person_task_register_id' => $user->person_id,
                'name' => $name,
                'num_participants' => 0,
            ]);
        }

        $result = [
            'id' => $group->id,
            'name' => $group->name,
            'participants' => [],
        ];

        return $result;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $group = TrainingTaskGroup::findOrFail($id);
        $training = $group->content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        $group->participants()->delete();
        $group->delete();

        return 'Grupo eliminado correctamente.';
    }

    public static function listParticipants(int $training_id)
    {
        $training = Training::findOrFail($training_id);

        $participants = $training->participants()
            ->with('person')
            ->where('is_active', true)
            ->get();

        $participantsMap = [];
        foreach ($participants as $participant) {
            $participantsMap[] = [
                'id' => $participant->id,
                'name' => $participant->person->names,
            ];
        }

        return $participantsMap;
    }

    public static function setParticipant(Request $request)
    {
        $user = User::authenticated();

        TrainingTaskGroupHelper::validateSetParticipantRequest($request);

        $groupId = $request->input('training_task_group_id');
        $participantId = $request->input('training_participant_id');

        $group = TrainingTaskGroup::findOrFail($groupId);
        $training = $group->content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        if ($group->score !== null && $group->score >= 0) {
            throw new Exception('No se puede modificar el grupo porque que ya ha registrado respuesta.');
        }

        $training = $group->content->contentGroup->training;
        $participant = TrainingParticipant::findOrFail($participantId);

        if ($participant->training_id != $training->id) {
            throw new Exception('El participante no pertenece a la capacitación.');
        }

        $groupParticipant = TrainingTaskGroupParticipant::select()
            ->where('training_participant_id', $participantId)
            ->first();

        if ($groupParticipant) {
            $groupParticipant->update([
                'training_task_group_id' => $groupId,
            ]);
        } else {
            $groupParticipant = TrainingTaskGroupParticipant::create([
                'training_task_group_id' => $groupId,
                'training_participant_id' => $participantId,
            ]);
        }

        $countParticipants = TrainingTaskGroupParticipant::select()
            ->where('training_task_group_id', $groupId)
            ->count();

        $group->update([
            'num_participants' => $countParticipants,
        ]);

        $result = [
            'id' => $groupParticipant->id,
            'name' => $participant->person->names,
            'training_participant_id' => $participant->id,
        ];

        return $result;
    }

    public static function deleteParticipant(int $id)
    {
        $user = User::authenticated();

        $groupParticipant = TrainingTaskGroupParticipant::findOrFail($id);

        $group = $groupParticipant->taskGroup;
        $training = $group->content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        $groupParticipant->delete();

        $countParticipants = TrainingTaskGroupParticipant::select()
            ->where('training_task_group_id', $group->id)
            ->count();

        $group->update([
            'num_participants' => $countParticipants,
        ]);

        return 'Participante quitado del grupo correctamente.';
    }
}

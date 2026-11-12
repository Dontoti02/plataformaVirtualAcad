<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\File\Repositories\LinkRepository;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\TrainingAnswer;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroupParticipant;
use Modules\Tenant\Modules\User\Models\User;

class TrainingAnswerRepository
{
    public static function list(int $training_content_id)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        $content = TrainingContent::byKey('id', $training_content_id);

        $training_id = $content->contentGroup->training_id;
        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);

        $select = TrainingAnswer::select([
            'training_answer.id',
            'person.id as person_id',
            'person.names as person',
            'user.avatar as photo',
            'training_answer.created_at as date',
            'training_answer.status',
            'training_answer.score',
        ])
            ->join('person', 'training_answer.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('training_answer.training_content_id', $training_content_id)
            ->whereIn('training_answer.status', [
                TrainingAnswerStatusEnum::DELIVERED,
                TrainingAnswerStatusEnum::OVERDUE,
                TrainingAnswerStatusEnum::EVALUATED,
            ])
            ->orderBy('training_answer.updated_at', 'asc')
            ->get();

        $answers = [];
        foreach ($select as $item) {
            $answers[] = [
                'id' => $item->id,
                'person_id' => $item->person_id,
                'person' => $item->person,
                'photo' => $item->photo,
                'date' => $item->date,
                'status' => $item->status,
                'score' => $item->score ? (float) $item->score : null,
                'final_note' => TrainingAverageRepository::getFinalNote($training_id, $item->person_id),
                'files' => FileRepository::listByModel($item),
                'links' => LinkRepository::listByModel($item),
            ];
        }

        $result = [
            'id' => $content->id,
            'title' => $content->title,
            'score' => $content->score ? (float) $content->score : null,
            'has_evaluation_form' => $content->has_evaluation_form,
            'form_uuid' => $content->form->uuid ?? null,
            'answers' => $answers,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $answer = TrainingAnswer::select('id', 'status', 'score')
            ->where('id', $id)
            ->first();

        $result = [
            'id' => $answer->id,
            'status' => $answer->status,
            'score' => $answer->score ? (float) $answer->score : null,
            'files' => FileRepository::listByModel($answer),
            'links' => LinkRepository::listByModel($answer),
        ];

        return $result;
    }

    public static function delivered(int $id)
    {
        $user = User::authenticated();

        $answer = TrainingAnswer::findOrFail($id);
        $content = $answer->content;
        $training = $content->contentGroup->training;

        TrainingHelper::checkTrainingStatus($user, $training);
        PeriodHelper::validateByTraining($training->id);

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if ($answer->status == TrainingAnswerStatusEnum::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $date = Carbon::now();
        $dateStart = Carbon::parse($content->date_start);
        $dateLimit = Carbon::parse($content->date_limit);

        if (!$content->is_open && ($date < $dateStart || $date > $dateLimit)) {
            throw new Exception('La tarea no admite respuestas');
        }

        $status = $answer->status == TrainingAnswerStatusEnum::PENDING
            ? ($date <= $dateLimit ? TrainingAnswerStatusEnum::DELIVERED : TrainingAnswerStatusEnum::OVERDUE)
            : TrainingAnswerStatusEnum::PENDING;

        $answersId = [$answer->id];

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

            $answers = TrainingAnswer::select()
                ->whereIn('person_id', $participants->pluck('person_id')->toArray())
                ->where('training_content_id', $content->id)
                ->get();

            $answersId = $answers->pluck('id')->toArray();
        }

        TrainingAnswer::select()
            ->whereIn('id', $answersId)
            ->update([
                'status' => $status,
            ]);

        if ($status != TrainingAnswerStatusEnum::PENDING) {
            $subject = 'Notificación de respuesta entregada';
            $body = EmailBodyTemplate::deliveredTrainingAnswer;
            self::sendMail([
                'subject' => $subject,
                'body' => $body,
                'name' => $content->contentGroup->training->teacher->person->names,
                'email' => $content->contentGroup->training->teacher->person->email,
                'trainingName' => $content->contentGroup->training->name,
                'studentName' => $user->person->names,
            ]);
        }

        return $status;
    }

    private static function sendMail(array $info)
    {
        $info = (object) $info;

        $subject = $info->subject;
        $body = $info->body;

        $name = $info->name;
        $email = $info->email;
        $trainingName = $info->trainingName;
        $studentName = $info->studentName;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{trainingName}}', $trainingName, $body);
        $body = str_replace('{{studentName}}', $studentName, $body);

        $data = (object) [
            'subject' => $subject,
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }
}

<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\File\Repositories\LinkRepository;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Modules\Training\Helpers\TrainingContentHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingAnswer;
use Modules\Tenant\Modules\Training\Models\TrainingAverage;
use Modules\Tenant\Modules\Training\Models\TrainingAverageDetail;
use Modules\Tenant\Modules\Training\Models\TrainingContent;
use Modules\Tenant\Modules\Training\Models\TrainingContentGroup;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroup;
use Modules\Tenant\Modules\Training\Models\TrainingTaskGroupParticipant;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;
use Modules\Tenant\Services\FileTenantService;

class TrainingContentRepository
{
    public static function list(int $training_id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::TRAINING_STUDENT;

        $training = Training::byKey('id', $training_id);

        TrainingHelper::checkTrainingStatus($user, $training);

        $syllabus = TrainingContentGroup::firstOrCreate([
            'title' => 'Sílabo',
            'training_id' => $training_id,
        ]);

        $syllabusMap = [
            ...$syllabus->only(['id', 'title']),
            'files' => FileRepository::listByModel($syllabus),
        ];

        $contentGroups = TrainingContentGroup::select()
            ->where('training_id', $training_id)
            ->whereNot('title', 'Sílabo')
            ->get();

        $contentGroupsMap = [];
        foreach ($contentGroups as $contentGroup) {

            $contents = $contentGroup->contents()
                ->select([
                    'id',
                    'title',
                    'created_at as date',
                    'type',
                    'is_visible',
                    'date_start',
                    'date_limit',
                    'is_open',
                    'is_group_task',
                ])->get();

            $contentsMap = [];
            foreach ($contents as $content) {
                self::manageStates($content);

                if ($is_student && !$content->is_visible) {
                    continue;
                }

                $contentsMap[] = $content;
            }

            if ($is_student && !count($contentsMap)) {
                continue;
            }

            $contentGroupsMap[] = [
                'id' => $contentGroup->id,
                'title' => $contentGroup->title,
                'contents' => $contentsMap,
            ];
        }

        $evaluation_groups = $training->evaluationGroups()->count();

        $hasCompetencies = $evaluation_groups > 0 ? true : false;

        $result = [
            'syllabus' => $syllabusMap,
            'content_groups' => $contentGroupsMap,
            'hasCompetencies' => $hasCompetencies
        ];

        return $result;
    }

    public static function detail(int $id)
    {
        $user = User::authenticated();

        $content = TrainingContent::findOrFail($id);
        $training = $content->contentGroup->training;

        self::manageStates($content);

        if ($user->rol_id == RolTenant::TRAINING_TEACHER) {

            TrainingHelper::validateTeacherAccess($user->person_id, $training->id);

            $evaluation_groups = $training->evaluationGroups()->count();

            $hasCompetencies = $evaluation_groups > 0 ? true : false;

            $content = self::get($content->id);

            return [
                ...$content,
                'hasCompetencies' => $hasCompetencies
            ];
        }

        TrainingHelper::validateStudentAccess($user->person_id, $training->id);

        $content = self::get($content->id, $user->person_id);

        return $content;
    }

    public static function get(int $id, $person_id = null)
    {
        $content = TrainingContent::findOrFail($id);

        $result = [
            'id' => $content->id,
            'training_content_group_id' => $content->training_content_group_id,
            'training_evaluation_group_id' => $content->training_evaluation_group_id,
            'title' => $content->title,
            'description' => $content->description,
            'date' => $content->created_at,
            'type' => $content->type,
            'is_visible' => $content->is_visible,
            'date_start' => $content->date_start,
            'date_limit' => $content->date_limit,
            'is_open' => $content->is_open,
            'score' => $content->score,
            'has_evaluation_form' => $content->has_evaluation_form,
            'is_group_task' => $content->is_group_task,
            'files' => FileRepository::listByModel($content),
            'links' => LinkRepository::listByModel($content),
            'comments' => TrainingCommentRepository::listByModel($content),
        ];

        if ($person_id) {
            $answer = $content->answers()
                ->where('training_answer.person_id', $person_id)
                ->first();

            $result['answer'] = TrainingAnswerRepository::get($answer->id);

            if ($content->has_evaluation_form) {
                $result['form_uuid'] = $content->form->uuid;
            }
        } else {
            if ($content->has_evaluation_form && $content->form) {
                $result['form'] = $content->form->load('questions');
            }
        }

        return $result;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        TrainingContentHelper::validateRequest($request);
        TrainingContentHelper::validateDateStartAndLimit($request);

        $contentGroup = TrainingContentGroup::findOrFail($request->training_content_group_id);
        $training = $contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $content = TrainingContent::create([
            'training_content_group_id' => $request->training_content_group_id,
            'training_evaluation_group_id' => $request->training_evaluation_group_id ?? null,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'is_visible' => false,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'is_open' => false,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
            'is_group_task' => $request->is_group_task ?? false,
        ]);

        $date = Carbon::now();

        $answersRecords = [];
        foreach ($training->participants as $participant) {
            $answersRecords[] = [
                'person_id' => $participant->person_id,
                'training_content_id' => $content->id,
                'status' => TrainingAnswerStatusEnum::PENDING,
                'score' => 0,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        TrainingAnswer::insert($answersRecords);

        if (in_array($request->type, ['task', 'evaluation'])) {
            $existsAverage = TrainingAverage::select()
                ->where('training_evaluation_group_id', $request->training_evaluation_group_id)
                ->exists();

            if (!$existsAverage) {
                $averagesRecords = [];
                foreach ($training->participants as $participant) {
                    $averagesRecords[] = [
                        'person_id' => $participant->person_id,
                        'training_evaluation_group_id' => $request->training_evaluation_group_id,
                        'score' => 0,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                }

                TrainingAverage::insert($averagesRecords);
            }

            $existsAverageDetail = TrainingAverageDetail::select()
                ->where('training_content_group_id', $request->training_content_group_id)
                ->where('training_evaluation_group_id', $request->training_evaluation_group_id)
                ->exists();

            if (!$existsAverageDetail) {
                $averageDetailsRecords = [];
                foreach ($training->participants as $participant) {
                    $averageDetailsRecords[] = [
                        'person_id' => $participant->person_id,
                        'training_content_group_id' => $request->training_content_group_id,
                        'training_evaluation_group_id' => $request->training_evaluation_group_id,
                        'score' => 0,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                }

                TrainingAverageDetail::insert($averageDetailsRecords);
            }
        }

        if ($request->type == 'evaluation' && $request->has_evaluation_form) {
            TrainingEvaluationFormRepository::set($content, $request->form, false);
        }

        if ($request->type == 'task' && $request->is_group_task) {
            if (!$request->filled('groups')) {
                throw new Exception('Se deben asignar participantes a los grupos.');
            }

            self::setTaskGroup($request, $training, $content, $user);
        }

        $result = self::get($content->id);

        $subject = 'Notificación de nuevo contenido';
        $body = EmailBodyTemplate::createTrainingContent;
        foreach ($training->participants as $participant) {
            self::sendMail([
                'subject' => $subject,
                'body' => $body,
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'trainingName' => $content->contentGroup->training->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $content = TrainingContent::findOrFail($id);
        TrainingContentHelper::validateRequest($request, $id);
        TrainingContentHelper::validateDateStartAndLimit($request, $content->date_start);

        $training = $content->contentGroup->training;

        TrainingHelper::validateTeacherAccess($user->person_id, $training->id);
        PeriodHelper::validateByTraining($training->id);

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $isGroupTask = $content->is_group_task;
        $requestIsGroupTask = $request->is_group_task;

        $hasEvaluationForm = $content->has_evaluation_form;
        $requestHasEvaluationForm = $request->has_evaluation_form;

        $content->update([
            'training_content_group_id' => $request->training_content_group_id,
            'training_evaluation_group_id' => $request->training_evaluation_group_id ?? null,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
            'is_group_task' => $request->is_group_task ?? false,
        ]);

        if ($isGroupTask && !$requestIsGroupTask) {
            $content->taskGroups()->each(function ($taskGroup) {
                $taskGroup->participants()->delete();
                $taskGroup->delete();
            });
        }

        if (!$isGroupTask && $requestIsGroupTask) {
            if (!$request->filled('groups')) {
                throw new Exception('Se deben asignar participantes a los grupos.');
            }

            self::setTaskGroup($request, $training, $content, $user);
        }

        if ($hasEvaluationForm && !$requestHasEvaluationForm) {
            TrainingEvaluationFormRepository::delete($content);
        }

        if ($requestHasEvaluationForm) {
            TrainingEvaluationFormRepository::set($content, $request->form, $hasEvaluationForm);
        }

        $result = self::get($content->id);

        $subject = 'Notificación de contenido actualizado';
        $body = EmailBodyTemplate::updateTrainingContent;
        foreach ($training->participants as $participant) {
            self::sendMail([
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'subject' => $subject,
                'body' => $body,
                'trainingName' => $content->contentGroup->training->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function updateVisibility(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = TrainingContent::byKey('id', $id);

        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $is_visible = !$content->is_visible;
        $date_limit = $content->date_limit;
        $is_open = $content->is_open;

        if (!$is_visible) {
            $date = Carbon::now();
            $is_open = false;

            if ($date <= $date_limit) {
                $date_limit = $date;
            }
        }

        $content->update([
            'is_visible' => $is_visible,
            'date_limit' => $date_limit,
            'is_open' => $is_open,
        ]);

        return $is_visible;
    }

    public static function updateStatus(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = TrainingContent::byKey('id', $id);

        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $date_limit = $content->date_limit;
        $is_open = !$content->is_open;

        if (!$is_open) {
            $date = Carbon::now();

            if ($date <= $date_limit) {
                $date_limit = $date;
            }
        }

        $content->update([
            'date_limit' => $date_limit,
            'is_open' => $is_open,
        ]);

        return $is_open;
    }

    public static function delete(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = TrainingContent::byKey('id', $id);

        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validateTeacherAccess($user->person_id, $training_id);
        PeriodHelper::validateByTraining($training_id);

        $files = $content->files;
        FileTenantService::delete($files->all());

        DB::beginTransaction();
        try {
            $content->links()->delete();
            $content->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return "Contenido eliminado correctamente";
    }

    private static function sendMail(array $info)
    {
        $info = (object) $info;

        $subject = $info->subject;
        $body = $info->body;

        $name = $info->name;
        $email = $info->email;
        $trainingName = $info->trainingName;
        $teacherName = $info->teacherName;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{trainingName}}', $trainingName, $body);
        $body = str_replace('{{teacherName}}', $teacherName, $body);

        $data = (object) [
            'subject' => $subject,
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }

    public static function report($request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);

        TrainingHelper::validateReportRequest($request);

        $training_id = $request->training_id;
        TrainingHelper::validateASTSAccess($user->person_id, $user->rol_id, $training_id);

        $training = Training::byKey('id', $training_id);

        $syllabus = $training->contentGroups()
            ->selectRaw("
                id,
                title as content_group_title,
                title,
                title as type,
                NULL as evaluation_group_title,
                created_at as date,
                NULL as date_limit,
                NULL as status
            ")
            ->where('title', 'Sílabo')
            ->get();

        $contents = TrainingContent::selectRaw("
                training_content.id,
                training_content_group.title as content_group_title,
                training_content.title,
                CASE
                    WHEN training_content.type = 'task' THEN 'Tarea'
                    WHEN training_content.type = 'evaluation' THEN 'Evaluación'
                    ELSE 'Contenido'
                END as type,
                training_evaluation_group.title as evaluation_group_title,
                training_content.created_at as date,
                training_content.date_limit,
                CASE WHEN training_content.is_visible = 1 THEN 'Visible' ELSE 'No visible' END as status
            ")
            ->join('training_content_group', 'training_content.training_content_group_id', 'training_content_group.id')
            ->leftJoin('training_evaluation_group', 'training_content.training_evaluation_group_id', 'training_evaluation_group.id')
            ->where('training_content_group.training_id', $training_id)
            ->whereNot('training_content_group.title', 'Sílabo')
            ->orderBy('training_content_group.id', 'asc')
            ->orderBy('training_content.created_at', 'asc')
            ->get();


        $result = [
            ...$syllabus->toArray(),
            ...$contents->toArray(),
        ];

        return [$result, $training];
    }

    public static function manageStates(TrainingContent $content)
    {
        if ($content->date_start && $content->date_limit) {
            $date = Carbon::now();

            $in_range = (bool) ($date >= $content->date_start && $date <= $content->date_limit);

            if ($content->is_open != $in_range || (!$content->is_visible && $in_range)) {
                $content->update([
                    'is_visible' => (bool) $content->is_visible || (!$content->is_visible && $in_range),
                    'is_open' => $in_range,
                ]);
            }
        }
    }

    public static function setTaskGroup(Request $request, Training $training, TrainingContent $content, User $user)
    {
        $groups = $request->input('groups');

        $participantIds = array_merge(...array_column($groups, 'participants'));

        $countParticipants = TrainingParticipant::select()
            ->whereIn('id', $participantIds)
            ->where('training_id', $training->id)
            ->count();

        if ($countParticipants != count($participantIds)) {
            throw new Exception('Algunos participantes no pertenecen a la capacitación.');
        }

        $participantsRecords = [];
        foreach ($groups as $group) {
            $newGroup = TrainingTaskGroup::create([
                'training_id' => $training->id,
                'training_content_id' => $content->id,
                'person_task_register_id' => $user->person_id,
                'name' => $group['name'],
                'num_participants' => count($group['participants']),
            ]);

            foreach ($group['participants'] as $participantId) {
                $participantsRecords[] = [
                    'training_task_group_id' => $newGroup->id,
                    'training_participant_id' => $participantId,
                ];
            }
        }

        $existingParticipants = [];
        foreach ($participantsRecords as $record) {
            if (in_array($record['training_participant_id'], $existingParticipants)) {
                throw new Exception('Existen participantes asignados mas de una vez en los grupos.');
            }

            $existingParticipants[] = $record['training_participant_id'];
        }

        TrainingTaskGroupParticipant::insert($participantsRecords);
    }
}

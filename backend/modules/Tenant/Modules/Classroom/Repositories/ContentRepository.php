<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ContentHelper;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Indicator;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\ContentGroup;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\EvaluationForm\Repositories\EvaluationFormRepository;
use Modules\Tenant\Modules\File\Repositories\FileRepository;
use Modules\Tenant\Modules\File\Repositories\LinkRepository;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;
use Modules\Tenant\Services\FileTenantService;

class ContentRepository
{
    public static function list(int $classroom_id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $is_student = $user->rol_id === RolTenant::STUDENT;

        $classroom = Classroom::byKey('id', $classroom_id);

        $syllabus = ContentGroup::firstOrCreate([
            'title' => 'Documentos Pedagógicos',
            'classroom_id' => $classroom_id,
        ]);

        $syllabusMap = [
            ...$syllabus->only(['id', 'title']),
            'files' => FileRepository::listByModel($syllabus),
        ];

        $contentGroups = ContentGroup::select()
            ->where('classroom_id', $classroom_id)
            ->whereNotIn('title', ['Documentos Pedagógicos', 'Sílabo'])
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

        $hasCompetencies = $classroom->capacities()->exists();

        $result = [
            'syllabus' => $syllabusMap,
            'content_groups' => $contentGroupsMap,
            'hasCompetencies' => $hasCompetencies
        ];

        return $result;
    }

    public static function detail(int $id)
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        $content = Content::byKey('id', $id);
        $classroom_id = $content->contentGroup->classroom_id;

        self::manageStates($content);

        if ($user->rol_id == RolTenant::TEACHER) {

            ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);

            $classroom = Classroom::byKey('id', $classroom_id);

            // Con capacidades: sin al menos una no hay dónde colgar tareas y evaluaciones.
            $hasCompetencies = $classroom->capacities()->exists();

            $content = self::get($content->id);

            return [
                ...$content,
                'hasCompetencies' => $hasCompetencies
            ];
        }

        ClassroomHelper::validateStudentAccess($user->person_id, $classroom_id);

        $content = self::get($content->id, $user->person_id);

        return $content;
    }

    public static function get(int $id, $person_id = null)
    {
        $content = Content::byKey('id', $id);

        $result = [
            'id' => $content->id,
            'content_group_id' => $content->content_group_id,
            'evaluation_group_id' => $content->evaluation_group_id,
            'indicator_id' => $content->indicator_id,
            'capacity_id' => $content->indicator?->capacity_id,
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
            'files' => FileRepository::listByModel($content),
            'links' => LinkRepository::listByModel($content),
            'comments' => CommentRepository::listByModel($content),
        ];

        if ($person_id) {
            $answer = $content->answers()
                ->where('answer.person_id', $person_id)
                ->first();

            $result['answer'] = AnswerRepository::get($answer->id);

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
        $session = SessionManager::get();
        $user = User::find($session->id);

        ContentHelper::validateRequest($request);
        ContentHelper::validateDateStartAndLimit($request);

        $classroom_id = ContentGroup::byKey('id', $request->content_group_id)->classroom_id;

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);
        $indicator_id = self::indicatorFor($request, $classroom_id);

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $content = Content::create([
            'content_group_id' => $request->content_group_id,
            'indicator_id' => $indicator_id,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'is_visible' => false,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'is_open' => false,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
        ]);

        $date = Carbon::now();
        $participants = Participant::where('classroom_id', $classroom_id)->get();

        $records = [];
        foreach ($participants as $participant) {
            $records[] = [
                'person_id' => $participant->person_id,
                'content_id' => $content->id,
                'status' => AnswerStatus::PENDING,
                'score' => 0,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        Answer::insert($records);

        // Las notas por indicador y capacidad se escriben al calificar
        // (CapacityScoreRepository): no hace falta crearlas en cero aquí.

        if ($request->type == 'evaluation' && $request->has_evaluation_form) {
            EvaluationFormRepository::set($content, $request->form, false);
        }

        $result = self::get($content->id);

        $subject = 'Notificación de nuevo contenido';
        $body = EmailBodyTemplate::createContent;
        foreach ($participants as $participant) {
            self::sendMail([
                'subject' => $subject,
                'body' => $body,
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'courseName' => $content->contentGroup->classroom->course->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = Content::byKey('id', $id);
        ContentHelper::validateRequest($request, $id);
        ContentHelper::validateDateStartAndLimit($request, $content->date_start);

        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);
        $indicator_id = self::indicatorFor($request, $classroom_id);
        $indicatorChanged = (int) $content->indicator_id !== (int) $indicator_id;

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $hasEvaluationForm = $content->has_evaluation_form;

        $content->update([
            'content_group_id' => $request->content_group_id,
            'indicator_id' => $indicator_id,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
        ]);

        if ($hasEvaluationForm && !$request->has_evaluation_form) {
            EvaluationFormRepository::delete($content);
        } else if(($hasEvaluationForm && $request->has_evaluation_form)|| (!$hasEvaluationForm && $request->has_evaluation_form)) {
            EvaluationFormRepository::set($content, $request->form, $hasEvaluationForm == $request->has_evaluation_form);
        }

        // Las notas ya puestas pasan con el contenido a su nuevo indicador.
        if ($indicatorChanged) {
            $personIds = Answer::where('content_id', $content->id)
                ->where('status', AnswerStatus::EVALUATED)
                ->pluck('person_id');

            foreach ($personIds as $personId) {
                CapacityScoreRepository::recalculate($classroom_id, $personId);
            }
        }

        $result = self::get($content->id);

        $participants = Participant::where('classroom_id', $classroom_id)->get();

        $subject = 'Notificación de contenido actualizado';
        $body = EmailBodyTemplate::updateContent;
        foreach ($participants as $participant) {
            self::sendMail([
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'subject' => $subject,
                'body' => $body,
                'courseName' => $content->contentGroup->classroom->course->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function updateVisibility(int $id)
    {
        $session = SessionManager::get();

        $user = User::find($session->id);
        $content = Content::byKey('id', $id);

        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

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
        $content = Content::byKey('id', $id);

        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

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
        $content = Content::byKey('id', $id);

        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateTeacherAccess($user->person_id, $classroom_id);
        PeriodHelper::validateByClassroom($classroom_id);

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
        $courseName = $info->courseName;
        $teacherName = $info->teacherName;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{courseName}}', $courseName, $body);
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

        ClassroomHelper::validateReportRequest($request);

        $classroom_id = $request->classroom_id;
        ClassroomHelper::validateASTSAccess($user->person_id, $user->rol_id, $classroom_id);

        $classroom = Classroom::byKey('id', $classroom_id);

        $syllabus = $classroom->contentGroups()
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
            ->whereIn('title', ['Documentos Pedagógicos', 'Sílabo'])
            ->get();

        $contents = Content::selectRaw("
                content.id,
                content_group.title as content_group_title,
                content.title,
                CASE
                    WHEN content.type = 'task' THEN 'Tarea'
                    WHEN content.type = 'evaluation' THEN 'Evaluación'
                    ELSE 'Contenido'
                END as type,
                CASE WHEN capacity.id IS NULL THEN NULL
                    ELSE CONCAT(capacity.name, ' / ', indicator.name) END as evaluation_group_title,
                content.created_at as date,
                content.date_limit,
                CASE WHEN content.is_visible = 1 THEN 'Visible' ELSE 'No visible' END as status
            ")
            ->join('content_group', 'content.content_group_id', 'content_group.id')
            ->leftJoin('indicator', 'content.indicator_id', 'indicator.id')
            ->leftJoin('capacity', 'indicator.capacity_id', 'capacity.id')
            ->where('content_group.classroom_id', $classroom_id)
            ->whereNotIn('content_group.title', ['Documentos Pedagógicos', 'Sílabo'])
            ->orderBy('content_group.id', 'asc')
            ->orderBy('content.created_at', 'asc')
            ->get();


        $result = [
            ...$syllabus->toArray(),
            ...$contents->toArray(),
        ];

        return [$result, $classroom];
    }

    /**
     * El indicador de una tarea o evaluación, validando que sea de esta misma
     * clase: si no, un docente podría colgar su tarea de otra clase.
     */
    private static function indicatorFor(Request $request, int $classroom_id): ?int
    {
        if (!in_array($request->type, ['task', 'evaluation']) || !$request->filled('indicator_id')) {
            return null;
        }

        $indicator = Indicator::byKey('id', (int) $request->indicator_id);

        if ((int) $indicator->capacity->classroom_id !== $classroom_id) {
            throw new Exception('El indicador no pertenece a esta clase.');
        }

        return $indicator->id;
    }

    public static function manageStates(Content $content)
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
}

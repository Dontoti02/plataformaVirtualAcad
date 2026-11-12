<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Modules\Training\Enums\TrainingStatusEnum;
use Modules\Tenant\Modules\Training\Helpers\TrainingHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingAnswer;
use Modules\Tenant\Modules\Training\Models\TrainingCategory;
use Modules\Tenant\Modules\Training\Models\TrainingParticipant;
use Modules\Tenant\Modules\Training\Models\TrainingTeacher;
use Modules\Tenant\Modules\Training\Templates\ListTemplate;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;


class TrainingRepository
{
    public static function filters()
    {
        $user = User::authenticated();

        $categories = TrainingCategory::all();

        $isAdmin = $user->rol_id === RolTenant::TRAINING_ADMINISTRADOR;

        $result = [
            'categories' => $categories,
            'is_admin' => $isAdmin,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $roles = [
            RolTenant::TRAINING_ADMINISTRADOR,
            RolTenant::TRAINING_TEACHER,
            RolTenant::TRAINING_STUDENT,
        ];
        $user = User::authenticated($roles);
        $rolId = $user->rol_id;

        $isStudent = $rolId === RolTenant::TRAINING_STUDENT;
        $isTeacher = $rolId === RolTenant::TRAINING_TEACHER;

        TrainingHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $onlyCompleted = $request->input('only_completed');
        $trainingCategoryId = $request->input('training_category_id');

        $columns = [
            'training.id',
            'training_category.id as training_category_id',
            'training_category.name as training_category_name',
            'training_status.id as training_status_id',
            'training_status.name as training_status_name',
            'training.name',
            'training.image',
            DB::raw('(
                SELECT COUNT(*) 
                FROM training_participant 
                WHERE training_participant.training_id = training.id
            ) as students'),
            'training.start_date',
            'training.end_date',
            'training.max_participants',
            'training.short_description',
            'training.created_at',
        ];

        if ($rolId === RolTenant::TRAINING_STUDENT) {
            $columns[] = 'training_participant.is_favorite';
        }

        $trainings = Training::select($columns)
            ->join('training_category', 'training.training_category_id', '=', 'training_category.id')
            ->join('training_status', 'training.training_status_id', '=', 'training_status.id')
            ->leftJoin('training_participant', function ($join) use ($user) {
                $join->on('training.id', '=', 'training_participant.training_id')
                    ->where('training_participant.person_id', $user->person_id)
                    ->whereNull('training_participant.deleted_at');
            })
            ->leftJoin('training_teacher', function ($join) use ($user) {
                $join->on('training.id', '=', 'training_teacher.training_id')
                    ->where('training_teacher.person_id', $user->person_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('training.name', 'like', "%{$search}%");
            })
            ->when($onlyCompleted, function ($query) {
                $query->where('training.training_status_id', TrainingStatusEnum::COMPLETED);
            })
            ->when($trainingCategoryId, function ($query) use ($trainingCategoryId) {
                $query->where('training.training_category_id', $trainingCategoryId);
            })
            ->when($rolId === RolTenant::TRAINING_TEACHER, function ($query) use ($user) {
                $query->where('training_teacher.person_id', $user->person_id);
            })
            ->when($rolId === RolTenant::TRAINING_STUDENT, function ($query) use ($user) {
                $query->where('training_participant.person_id', $user->person_id)
                    ->whereNotNull('training_participant.is_active');
            })
            ->when($rolId === RolTenant::TRAINING_STUDENT, function ($query) {
                $query->orderBy('training_participant.is_favorite', 'desc');
            })
            ->orderBy('training.name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $trainingsMap = [];
        foreach ($trainings->items() as $training) {
            $time = TrainingHelper::getMinutesRemaining($training);

            $add = [
                'id' => $training->id,
                'category' => $training->training_category_name,
                'status' => $training->training_status_name,
                'name' => $training->name,
                'image' => $training->image,
                'students' => $training->students,
                'max_participants' => $training->max_participants,
                'short_description' => $training->short_description,
                'minutes_remaining' => $time->minutesRemaining,
                'total_minutes' => $time->totalMinutes,
                'is_student' => $isStudent,
                'is_teacher' => $isTeacher,
                'start_date' => $training->start_date->format('d-m-Y'),
                'end_date' => $training->end_date->format('d-m-Y'),
                'status_id' => $training->training_status_id
            ];

            if ($training->is_student === 1) {
                $add['is_favorite'] = $training->is_favorite;
            }

            $trainingsMap[] = $add;
        }

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $trainings->total(),
            'data' => $trainingsMap,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $user = User::authenticated();

        $training = Training::findOrFail($id);

        TrainingHelper::checkTrainingStatus($user, $training);

        $trainingMap = [
            'id' => $training->id,
            'name' => $training->name,
            'image' => $training->image,
            'training_category_id' => $training->training_category_id,
            'num_max_absences' => $training->num_max_absences,
            'start_date' => $training->start_date->format('Y-m-d'),
            'end_date' => $training->end_date->format('Y-m-d'),
            'min_participants' => $training->min_participants,
            'max_participants' => $training->max_participants,
            'short_description' => $training->short_description,
            'long_description' => $training->long_description,
            'teacher' => $training->teacher?->person->names ?? null,
            'teacher_document_number' => $training->teacher?->person->document_number ?? null,
            'is_student' => $training->participants()->where('person_id', $user->person_id)->exists(),
            'is_teacher' => $training->teacher?->person_id === $user->person_id,
            'status_id' => $training->training_status_id
        ];

        return $trainingMap;
    }

    public static function set(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateSetRequest($request);

        $trainingId = $request->input('id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $minParticipants = $request->input('min_participants');
        $maxParticipants = $request->input('max_participants');
        $numMaxAbsences = $request->input('num_max_absences');

        if ($trainingId) {
            $training = Training::findOrFail($trainingId);

            $now = Carbon::now();
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $statusId = $training->training_status_id;

            if ($now->lessThan($startDate)) {
                $statusId = TrainingStatusEnum::NOT_STARTED;
            }

            if ($now->between($startDate, $endDate)) {
                $statusId = TrainingStatusEnum::IN_PROGRESS;

                if ($training->participants()->count() < $training->min_participants) {
                    $statusId = TrainingStatusEnum::NOT_STARTED;
                }
            }

            if ($now->greaterThan($endDate)) {
                $statusId = TrainingStatusEnum::COMPLETED;
            }

            if ($startDate !== $training->start_date || $endDate !== $training->end_date) {
                TrainingHelper::canUpdateDates($training, $request);
            }

            if ($minParticipants > $maxParticipants) {
                throw new Exception('El número mínimo de participantes no puede ser mayor al número máximo de participantes');
            }

            if ($maxParticipants < $training->participants()->count()) {
                throw new Exception('
                El número máximo de participantes no puede ser menor a la cantidad de participantes inscritos
                actualmente en la capacitación (' . $training->participants()->count() . ')');
            }

            if ($numMaxAbsences !== $training->num_max_absences) {

                $participantWithMinorAbsences = TrainingParticipant::select('training_participant.person_id', DB::raw('COUNT(training_assistance.id) as absences'))
                    ->join('training_assistance', 'training_participant.person_id', '=', 'training_assistance.person_id')
                    ->where('training_assistance.training_id', $training->id)
                    ->where('training_assistance.status', 'absence')
                    ->groupBy('training_participant.person_id')
                    ->having('absences', '>', $numMaxAbsences)
                    ->first();

                if ($participantWithMinorAbsences) {
                    throw new Exception('No se puede actualizar el número máximo de faltas porque hay estudiantes con más faltas de las permitidas.' .
                        $participantWithMinorAbsences->person->names . ' tiene ' . $participantWithMinorAbsences->absences . ' faltas');
                }
            }

            $training->update([
                'training_category_id' => $request->training_category_id,
                'training_status_id' => $statusId,
                'name' => $request->name,
                'num_max_absences' => $request->num_max_absences,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'min_participants' => $request->min_participants,
                'max_participants' => $request->max_participants,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
            ]);
        } else {
            $training = Training::create([
                'period_id' => PeriodHelper::current()->id,
                'training_category_id' => $request->training_category_id,
                'training_status_id' => TrainingStatusEnum::NOT_STARTED,
                'name' => $request->name,
                'num_max_absences' => $request->num_max_absences,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'min_participants' => $request->min_participants,
                'max_participants' => $request->max_participants,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
            ]);

            $trainingId = $training->id;
        }

        return $trainingId;
    }

    public static function delete(int $id)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        PeriodHelper::validateByTraining($id);

        $training = Training::findOrFail($id);

        if ($training->teacher) {
            throw new Exception('No se puede eliminar la capacitación porque tiene un docente asignado');
        }

        if ($training->participants()->exists()) {
            throw new Exception('No se puede eliminar la capacitación porque tiene participantes inscritos');
        }

        if ($training->assistances()->exists()) {
            throw new Exception('No se puede eliminar la capacitación porque tiene asistencias registradas');
        }

        if ($training->contentGroups()->exists()) {
            throw new Exception('No se puede eliminar la capacitación porque tiene grupos de contenido registrados');
        }

        if ($training->evaluationGroups()->exists()) {
            throw new Exception('No se puede eliminar la capacitación porque tiene grupos de evaluación registrados');
        }

        if ($training->publications()->exists()) {
            throw new Exception('No se puede eliminar la capacitación porque tiene publicaciones registradas');
        }

        $training->delete();

        return "Capacitación eliminada correctamente";
    }

    public static function updateImage(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateUpdateImageRequest($request);

        $trainingId = $request->input('training_id');

        PeriodHelper::validateByTraining($trainingId);

        $training = Training::findOrFail($trainingId);

        if ($training->image) {
            $training->image = 'public/' . $training->image;

            if (Storage::exists($training->image)) {
                Storage::delete($training->image);
            }
        }

        $file = $request->file('file');
        $path = $file->store('public/training');
        $path = str_replace('public/', '', $path);

        $training->update([
            'image' => $path,
        ]);

        return $path;
    }

    public static function deleteImage(int $id)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        $training = Training::findOrFail($id);

        if ($training->image) {
            $training->image = 'public/' . $training->image;

            if (Storage::exists($training->image)) {
                Storage::delete($training->image);
            }
        }

        $training->update([
            'image' => null,
        ]);

        return "Imagen eliminada correctamente";
    }

    public static function updateFavorite(int $id)
    {
        $user = User::authenticated(RolTenant::TRAINING_STUDENT);

        TrainingHelper::validateStudentAccess($user->person_id, $id);
        PeriodHelper::validateByTraining($id);

        $participant = TrainingParticipant::select()
            ->where('person_id', $user->person_id)
            ->where('training_id', $id)
            ->first();

        $participant->update([
            'is_favorite' => !$participant->is_favorite,
        ]);

        return "Capacitación " . ($participant->is_favorite === true ? 'marcada' : 'desmarcada') . " como favorita correctamente";
    }

    public static function findPerson(Request $request)
    {
        $search = $request->input('search');

        $persons = Person::select([
            'id',
            'document_number',
            'names',
            'email',
        ])
            ->whereHas('user')
            ->when($search, function ($query) use ($search) {
                $searchTerms = explode(' ', $search);
                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query
                            ->orWhere('document_number', 'like', "%{$term}%")
                            ->orWhere('names', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    }
                });
            })
            ->orderBy('names')
            ->get();

        return $persons;
    }

    public static function createPerson(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateCreatePersonRequest($request);

        $type = $request->input('type');
        $documentType = $request->input('document_type');
        $documentNumber = $request->input('document_number');
        $names = $request->input('names');
        $phone = $request->input('phone');
        $email = $request->input('email');

        $person = Person::create([
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'names' => $names,
            'phone' => $phone,
            'email' => $email,
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'email' => $email,
            'password' => Hash::make($documentNumber),
        ]);

        $rolId = $type === 'student' ? RolTenant::TRAINING_STUDENT : RolTenant::TRAINING_TEACHER;

        $user->roles()->attach($rolId);

        return $person->id;
    }

    public static function assignTeacher(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateAssignPersonRequest($request);

        $trainingId = $request->input('training_id');
        $personId = $request->input('person_id');

        PeriodHelper::validateByTraining($trainingId);

        $training = Training::findOrFail($trainingId);
        $person = Person::findOrFail($personId);

        $teacher = $training->teacher;

        if ($teacher) {
            if ($teacher->person_id == $personId) {
                throw new Exception('El docente ya está asignado en la capacitación');
            }

            $teacher->update([
                'person_id' => $personId,
            ]);
        } else {
            TrainingTeacher::create([
                'person_id' => $personId,
                'training_id' => $trainingId,
            ]);
        }

        if (!$person->user->roles->contains('id', RolTenant::TRAINING_TEACHER)) {
            $person->user->roles()->attach(RolTenant::TRAINING_TEACHER);
        }

        return 'Docente asignado a la capacitación correctamente';
    }

    public static function filtersStudents()
    {
        $roles = [
            [
                'key' => 'internal',
                'name' => 'Usuarios Internos'
            ],
            [
                'key' => 'external',
                'name' => 'Usuarios Externos'
            ]
        ];

        $status = [
            [
                'key' => 'active',
                'name' => 'Activos'
            ],
            [
                'key' => 'suspended',
                'name' => 'Suspendidos'
            ],
            [
                'key' => 'retired',
                'name' => 'Retirados'
            ]
        ];

        $result = [
            'roles' => $roles,
            'status' => $status,
        ];

        return $result;
    }

    public static function listStudents(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateListStudentsRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $trainingId = $request->input('training_id');
        $search = $request->input('search');
        $rol = $request->input('rol');
        $status = $request->input('status');

        $internalRolesId = [
            RolTenant::SECRETARY,
            RolTenant::TEACHER,
            RolTenant::STUDENT,
            RolTenant::ADMINISTRADOR,
        ];

        $participants = TrainingParticipant::select([
            'person.id',
            'person.names',
            'person.document_number',
            'person.email',
            DB::raw("(
                SELECT COUNT(*) 
                FROM training_assistance
                WHERE 
                    person_id = training_participant.person_id
                    AND training_id = training_participant.training_id
                    AND status = 'absence'
            ) as absences"),
            DB::raw("EXISTS (
                SELECT 1 
                FROM user
                INNER JOIN rol_user ON user.id = rol_user.user_id 
                WHERE 
                    user.person_id = person.id
                    AND rol_user.rol_id IN (" . implode(',', $internalRolesId) . ")
            ) as is_internal"),
            DB::raw("(
                CASE
                    WHEN training_participant.is_active IS NULL THEN 'retired'
                    WHEN training_participant.is_active = true THEN 'active'
                    ELSE 'suspended'
                END
            ) as status")
        ])
            ->join('person', 'training_participant.person_id', '=', 'person.id')
            ->where('training_participant.training_id', $trainingId)
            ->when($search, function ($query) use ($search) {
                $searchTerms = explode(' ', $search);
                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query
                            ->orWhere('person.names', 'like', "%{$term}%")
                            ->orWhere('person.document_number', 'like', "%{$term}%");
                    }
                });
            })
            ->when($rol, function ($query) use ($rol) {
                $query->having('is_internal', $rol === 'internal');
            })
            ->when($status, function ($query) use ($status) {
                $query->having('status', $status);
            })
            ->orderBy('person.names', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $totalParticipants = TrainingParticipant::select()
            ->where('training_id', $trainingId)
            ->count();

        $internalParticipantsCount = TrainingParticipant::select()
            ->where('training_id', $trainingId)
            ->whereHas('person.user.roles', function ($query) use ($internalRolesId) {
                $query->whereIn('rol_id', $internalRolesId);
            })
            ->count();

        $externalParticipantsCount = $totalParticipants - $internalParticipantsCount;

        $result = [
            'page' => $page,
            'size' => $size,
            'summary' => [
                'internal_users' => $internalParticipantsCount,
                'external_users' => $externalParticipantsCount,
            ],
            'total' => $participants->total(),
            'data' => $participants->items(),
        ];

        return $result;
    }

    public static function listStudentsDownload(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateListStudentsDownloadRequest($request);

        $trainingId = $request->input('training_id');
        $search = $request->input('search');
        $rol = $request->input('rol');
        $status = $request->input('status');

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $internalRolesId = [
            RolTenant::SECRETARY,
            RolTenant::TEACHER,
            RolTenant::STUDENT,
            RolTenant::ADMINISTRADOR,
        ];

        $participants = TrainingParticipant::select([
            'training_participant.id',
            'person.names',
            'person.document_number',
            DB::raw("(
                SELECT COUNT(*) 
                FROM training_assistance
                WHERE 
                    person_id = training_participant.person_id
                    AND training_id = training_participant.training_id
                    AND status = 'absence'
            ) as absences"),
            DB::raw("EXISTS (
                SELECT 1 
                FROM user
                INNER JOIN rol_user ON user.id = rol_user.user_id 
                WHERE 
                    user.person_id = person.id
                    AND rol_user.rol_id IN (" . implode(',', $internalRolesId) . ")
            ) as is_internal"),
            DB::raw("(
                CASE
                    WHEN training_participant.is_active IS NULL THEN 'retired'
                    WHEN training_participant.is_active = true THEN 'active'
                    ELSE 'suspended'
                END
            ) as status")
        ])
            ->join('person', 'training_participant.person_id', '=', 'person.id')
            ->where('training_participant.training_id', $trainingId)
            ->when($search, function ($query) use ($search) {
                $searchTerms = explode(' ', $search);
                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query
                            ->orWhere('person.names', 'like', "%{$term}%")
                            ->orWhere('person.document_number', 'like', "%{$term}%");
                    }
                });
            })
            ->when($rol, function ($query) use ($rol) {
                $query->having('is_internal', $rol === 'internal');
            })
            ->when($status, function ($query) use ($status) {
                $query->having('status', $status);
            })
            ->orderBy('person.names', 'asc')
            ->get();

        $rows = [];
        foreach ($participants as $indexItem => $item) {
            $rows[] = [
                'id' => $indexItem + 1,
                'names' => $item->names,
                'document_number' => $item->document_number,
                'absences' => $item->absences,
                'is_internal' => $item->is_internal ? 'Usuarios Internos' : 'Usuarios Externos',
                'status' => $item->status === 'active' ? 'Activo' : ($item->status === 'suspended' ? 'Suspendido' : 'Retirado'),
            ];
        }

        if (count($rows) === 0) {
            throw new Exception('¡No hay datos para exportar!');
        }

        $filters = [
            'CAPACITACIÓN' => Training::findOrFail($trainingId)->name,
            'BÚSQUEDA' => $search ?? null,
            'ROL' => $rol ? ($rol === 'internal' ? 'Usuarios Internos' : 'Usuarios Externos') : null,
            'ESTADO' => $status ? ($status === 'active' ? 'Activos' : ($status === 'suspended' ? 'Suspendidos' : 'Retirados')) : null,
        ];

        $columns = [
            'id' => '#',
            'names' => 'USUARIO',
            'document_number' => 'DNI',
            'absences' => 'FALTAS',
            'is_internal' => 'ROL',
            'status' => 'ESTADO',
        ];

        $columnsAligned = [
            'id',
            'document_number',
            'absences',
        ];

        $data = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'title' => 'USUARIOS INSCRITOS',
            'date' => 'FECHA: ' . Carbon::now()->format('d/m/Y h:i A'),
            'footer' => 'Total de usuarios inscritos: ' . count($rows),
            'filters' => $filters,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = ListTemplate::class;

        $result = Generate::generateXlsx($template, $data);

        return $result;
    }

    public static function assignStudent(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateAssignPersonRequest($request);

        $trainingId = $request->input('training_id');
        $personId = $request->input('person_id');

        PeriodHelper::validateByTraining($trainingId);

        $training = Training::findOrFail($trainingId);
        $person = Person::findOrFail($personId);

        $participant = $training->participants()->where('person_id', $personId)->first();

        if ($participant) {
            throw new Exception('El estudiante ya está asignado en la capacitación.');
        }

        if ($training->participants()->count() >= $training->max_participants) {
            throw new Exception('La capacitación ha alcanzado el límite de participantes.');
        }

        // foreach ($training->contentGroups as $contentGroup) {
        //     if ($contentGroup->contents()->whereNot('type', 'content')->exists()) {
        //         throw new Exception('No se puede registrar el participante porque ya hay tareas o evaluaciones creadas para la capacitación.');
        //     }
        // }

        TrainingParticipant::create([
            'person_id' => $personId,
            'training_id' => $trainingId,
        ]);

        if (!$person->user->roles->contains('id', RolTenant::TRAINING_STUDENT)) {
            $person->user->roles()->attach(RolTenant::TRAINING_STUDENT);
        }

        return 'Estudiante asignado a la capacitación correctamente.';
    }

    public static function unassignStudent(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateAssignPersonRequest($request);

        $trainingId = $request->input('training_id');
        $personId = $request->input('person_id');

        PeriodHelper::validateByTraining($trainingId);

        $training = Training::findOrFail($trainingId);
        $person = Person::findOrFail($personId);

        $participant = $training->participants()->where('person_id', $personId)->first();

        if (!$participant) {
            throw new Exception('El estudiante no está asignado en la capacitación');
        }

        if ($training->assistances()->where('person_id', $personId)->exists()) {
            throw new Exception('El estudiante no puede ser eliminado porque ya tiene asistencias registradas');
        }

        $participant->delete();

        $otherTrainings = TrainingParticipant::select()
            ->where('training_id', '!=', $trainingId)
            ->where('person_id', $personId)
            ->exists();

        if (!$otherTrainings && $person->user->roles->contains('id', RolTenant::TRAINING_STUDENT)) {
            $person->user->roles()->detach(RolTenant::TRAINING_STUDENT);
        }

        return 'Estudiante eliminado de la capacitación correctamente';
    }

    public static function activateStudent(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateActivateStudentRequest($request);

        $trainingId = $request->input('training_id');
        $personId = $request->input('person_id');
        $justification = $request->input('justification');

        PeriodHelper::validateByTraining($trainingId);

        $training = Training::findOrFail($trainingId);

        $participant = $training->participants()->where('person_id', $personId)->first();

        if (!$participant) {
            throw new Exception('El estudiante no está asignado en la capacitación');
        }

        $isActive = $participant->is_active == null ? true : null;

        $participant->update([
            'is_active' => $isActive,
            'justification' => $justification,
        ]);

        return 'Estudiante ' . ($isActive ? 'habilitado' : 'retirado') . ' correctamente';
    }

    public static function certificateDownload(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingHelper::validateCertificateDownloadRequest($request);

        $trainingId = $request->input('training_id');
        $personId = $request->input('person_id');

        $training = Training::findOrFail($trainingId);
        $person = Person::findOrFail($personId);

        $participant = $training->participants()->where('person_id', $personId)->first();

        if (!$participant) {
            throw new Exception('El estudiante no está asignado en la capacitación');
        }

        TrainingHelper::getMinutesRemaining($training);

        if ($training->training_status_id !== TrainingStatusEnum::COMPLETED) {
            throw new Exception('Para descargar el certificado, la capacitación debe finalizar.');
        }

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();
        $background = SystemConfigurationHelper::getCertificateBackground();
        $title = SystemConfigurationHelper::getValueByKey('certificate_title');
        $director = SystemConfigurationHelper::getValueByKey('certificate_director');
        $secretary = SystemConfigurationHelper::getValueByKey('certificate_secretary');
        $address = SystemConfigurationHelper::getValueByKey('certificate_address');

        $date = Carbon::now();
        $date = $date->format('d/m/y');

        $data = (object) [
            'background' => $background,
            'institutionLogo' => $institutionLogo,
            'institutionName' => $institutionName,
            'title' => $title,
            'student' => $person->names,
            'director' => $director,
            'secretary' => $secretary,
            'address' => $address,
            'date' => $date,
        ];

        $view = 'training/certificate';

        $result = Generate::generatePdf($view, $data, $data->student);

        return $result;
    }

    public static function certificateDownloadZip(int $id)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        $training = Training::findOrFail($id);

        $participants = $training->participants()
            ->where('is_active', true)
            ->get();

        TrainingHelper::getMinutesRemaining($training);

        if ($training->training_status_id !== TrainingStatusEnum::COMPLETED) {
            throw new Exception('Para descargar los certificados, la capacitación debe finalizar.');
        }

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();
        $background = SystemConfigurationHelper::getCertificateBackground();
        $title = SystemConfigurationHelper::getValueByKey('certificate_title');
        $director = SystemConfigurationHelper::getValueByKey('certificate_director');
        $secretary = SystemConfigurationHelper::getValueByKey('certificate_secretary');
        $address = SystemConfigurationHelper::getValueByKey('certificate_address');
 
        $date = Carbon::now();
        $date = $date->format('d/m/y');

        $view = 'training/certificate';

        $files = [];

        foreach ($participants as $participant) {
            $data = (object) [
                'background' => $background,
                'institutionLogo' => $institutionLogo,
                'institutionName' => $institutionName,
                'title' => $title,
                'student' => $participant->person->names,
                'director' => $director,
                'secretary' => $secretary,
                'address' => $address,
                'date' => $date,
            ];

            $files[] = Generate::generatePdf($view, $data, $data->student);
        }

        $result = Generate::generateZip($files, 'certificados');

        return $result;
    }

    public static function createCategory(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        $name = $request->input('name');

        $category = TrainingCategory::create([
            'name' => $name,
        ]);

        return $category;
    }
}

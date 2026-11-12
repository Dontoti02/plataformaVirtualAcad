<?php

namespace Modules\Tenant\Modules\Import\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Shared\Utils\Date;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\Student;
use Modules\Tenant\Modules\Import\Helpers\ImportHelper;
use Modules\Tenant\Modules\Import\Helpers\ImportStamp;
use Modules\Tenant\Modules\Import\Jobs\ImportJob;
use Modules\Tenant\Modules\Import\Models\Import;
use Modules\Tenant\Modules\Import\Models\ImportDetail;
use Modules\Tenant\Modules\Management\Models\Course;
use Modules\Tenant\Modules\Management\Models\Cycle;
use Modules\Tenant\Modules\Management\Models\Period;
use Modules\Tenant\Modules\Management\Models\ProductiveFamily;
use Modules\Tenant\Modules\Management\Models\ProgramPlan;
use Modules\Tenant\Modules\Management\Models\Section;
use Modules\Tenant\Modules\Management\Models\Shift;
use Modules\Tenant\Modules\Management\Models\Staff;
use Modules\Tenant\Modules\Management\Models\StudyPlan;
use Modules\Tenant\Modules\Management\Models\StudyProgram;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Shared\Services\CentralConfig;
use Modules\Tenant\Modules\User\Helpers\RolHelper;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\Rol;
use Modules\Tenant\Modules\User\Models\User;

class ImportRepository
{
    public static function list()
    {
        $imports = Import::selectRaw("
            import.id,
            import.key,
            import.title,
            (
                SELECT status 
                FROM import_detail 
                WHERE import_id = import.id 
                ORDER BY date DESC
                LIMIT 1
            ) AS last_status,
            (
                SELECT date
                FROM import_detail
                WHERE import_id = import.id
                ORDER BY date DESC
                LIMIT 1
            ) as last_date
        ")
            ->orderBy('import.id', 'asc')
            ->get();

        return $imports;
    }
    public static function finish(Request $request)
    {
        try {
            $import = Import::byKey('key', $request->key);
            $importId = $import->id;
            $import_detail = ImportDetail::where('import_id', $importId)
            ->where('is_active', true)
            ->first();

            if (!$import_detail) {
            throw new Exception('No se encontró el detalle de importación actual.');
            }

            $import_detail->update(['is_current' => false]);
            $import_detail->update(['is_active' => false]);
        } catch (Exception $e) {
            logger($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return "Importación finalizada";
    }
    public static function finishAll(){
        try {
            $import_detail = ImportDetail::where('is_active', true)
            ->get();

            if (!$import_detail) {
            throw new Exception('No se encontró el detalle de importación actual.');
            }

            foreach ($import_detail as $detail) {
                $detail->update(['is_current' => false]);
                $detail->update(['is_active' => false]);
            }
        } catch (Exception $e) {
            logger($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return "Importación finalizada";
    }
    public static function get(int $id)
    {
        $import = Import::find($id);

        $import_detail = $import->details()
            ->where('is_current', true)
            ->first();

        if (!$import_detail) {
            throw new Exception('Aun no se ha ejecutado la importación');
        }

        if ($import_detail->is_active) {
            throw new Exception('Importación en curso');
        }

        $result = [
            'id' => $import->id,
            'key' => $import->key,
            'title' => $import->title,
            'status' => $import_detail->status,
            'progress' => $import_detail->progress,
            'date' => $import_detail->date,
            'time_elapsed' => $import_detail->time_elapsed,
            'log' => json_decode($import_detail->log),
            'summary' => json_decode($import_detail->summary),
        ];

        return $result;
    }

    public static function currently()
    {
        $import = Import::select([
            'import.id',
            'import.key',
            'import.title',
            'import_detail.status',
            'import_detail.progress',
            'import_detail.date',
            'import_detail.time_elapsed',
            'import_detail.log',
            'import_detail.summary',
        ])
            ->join('import_detail', 'import.id', 'import_detail.import_id')
            ->where('import_detail.is_current', true)
            ->where('import_detail.is_active', true)
            ->first();

        if ($import) {
            $import->log = json_decode($import->log);
            $import->summary = json_decode($import->summary);
        }

        return $import;
    }

    public static function history(int $id)
    {
        $import = Import::find($id);

        $import_details = $import->details()
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($import_detail) {
                return [
                    'id' => $import_detail->id,
                    'status' => $import_detail->status,
                    'date' => $import_detail->date,
                    'log' => json_decode($import_detail->log),
                ];
            });

        $result = [
            'id' => $import->id,
            'key' => $import->key,
            'title' => $import->title,
            'details' => $import_details,
        ];

        return $result;
    }

    public static function execute(string $key, Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        $exists = ImportDetail::where('is_active', true)->exists();
        if ($exists) {
            throw new Exception('Ya existe una importación en curso');
        }

        $import = Import::byKey('key', $key);

        ImportHelper::validateRequest($request);

        $file = $request->file('file');
        $data = ImportHelper::extractData($file);
        
        // self::executeJob($import, $key, $data); // Usar para ejecutar en primer plano
        ImportJob::dispatch($import, $key, $data); // Usar para ejecutar en segundo plano

        return 'Importación iniciada';
    }

    public static function executeJob($import, $key, $data)
    {
        try {
            $now = Carbon::now();

            $import->details()
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                ]);

            $import_detail = $import->details()->create([
                'is_current' => true,
                'is_active' => true,
                'status' => 'pending',
                'progress' => 0,
                'date' => $now,
                'time_elapsed' => 0,
                'log' => json_encode(["$now | Importación iniciada"]),
            ]);
            $rows = ImportHelper::formatData($import, $import_detail, $data);

            // Todo lo que cree la importación lleva SU fecha: es lo que permite
            // deshacerla después sin tocar nada más. Ver ImportStamp.
            $summary = ImportStamp::during((string) $import_detail->date, function () use ($key, $import_detail, $rows) {
                switch ($key) {
                    case 'staff':
                        return self::importStaff($import_detail, $rows);
                    case 'study_programs':
                        return self::importStudyProgram($import_detail, $rows);
                    case 'registrations':
                        return self::importRegistration($import_detail, $rows);
                    case 'evaluations':
                        return self::importEvaluation($import_detail, $rows);
                    case 'admissions':
                        return null;   // sin implementar en esta versión
                    default:
                        throw new Exception('Tipo no admitido');
                }
            });

            $date = Carbon::parse($import_detail->date);
            $now = Carbon::now();

            $log = json_decode($import_detail->log);
            $log[] = "$now | Importación completada";

            $import_detail->update([
                'is_current' => true,
                'is_active' => false,
                'status' => 'completed',
                'progress' => 100,
                'time_elapsed' => $date->diffInMinutes($now),
                'log' => json_encode($log),
                'summary' => json_encode($summary),
            ]);
        } catch (Exception $e) {
            $date = Carbon::parse($import_detail->date);
            $now = Carbon::now();

            $log = json_decode($import_detail->log);
            $aux = $e->getMessage();
            $log[] = "$now | $aux";

            $import_detail->update([
                'is_current' => true,
                'is_active' => false,
                'status' => 'failed',
                'progress' => 100,
                'time_elapsed' => $date->diffInMinutes($now),
                'log' => json_encode($log),
            ]);

            logger($e->getMessage());
        }
    }

    private static function importStaff($import_detail, $rows)
    {
        $total = count($rows);
        $tenPercent = ceil($total / 10);

        $totalPeriods = 0;
        $totalStaffs = 0;
        $totalUsers = 0;

        $log = json_decode($import_detail->log);

        foreach ($rows as $index => $row) {

            if ($index % $tenPercent == 0) {
                $progress = round(($index / $total) * 100);

                if ($progress) {
                    $date = Carbon::parse($import_detail->date);
                    $now = Carbon::now();

                    $log[] = "$now | $progress% procesado";

                    $import_detail->update([
                        'progress' => $progress,
                        'time_elapsed' => $date->diffInMinutes($now),
                        'log' => json_encode($log),
                    ]);
                }
            }

            $period = Period::select()
                ->where('name', $row->period)
                ->first();
            if (is_null($period)) {
                $period = Period::create([
                    "name" => $row->period,
                ]);
                $totalPeriods++;
            }

            $person = Person::withTrashed()
                ->where('document_number', $row->document_number)
                ->first();
            if (is_null($person)) {
                $person = Person::create([
                    "document_type" => $row->document_type,
                    "document_number" => $row->document_number,
                    "names" => $row->names,
                    "phone" => $row->phone,
                    "email" => $row->email,
                ]);
            }
            if($person->trashed()) {
                $person->restore();
            }
            
            $staff = Staff::select()
                ->where('period_id', $period->id)
                ->where('person_id', $person->id)
                ->where('type', $row->type)
                ->first();
            if (is_null($staff)) {
                $staff = Staff::create([
                    "period_id" => $period->id,
                    "person_id" => $person->id,
                    "type" => $row->type,
                    "registration_date" => Date::invertDateFormat($row->registration_date),
                    "working_condition" => $row->working_condition,
                ]);
                $totalStaffs++;
            }

            $allowedStaffRoles = [
                'SECRETARIO ACADÉMICO', 'SECRETARIO ACADEMICO',
                'DOCENTE',
                'ADMINISTRADOR',
                'DIRECTOR GENERAL',
                'JEFE DE UNIDAD ACADÉMICA', 'JEFE DE UNIDAD ACADEMICA',
                'COORDINADOR ACADÉMICO', 'COORDINADOR ACADEMICO',
            ];

            if (in_array(trim($row->type), $allowedStaffRoles)) {
                $user = User::withTrashed()
                    ->where('person_id', $person->id)
                    ->first();
                if (is_null($user)) {
                    // El correo es único en user: si ya es de otra persona (hay
                    // institutos que ponen su correo genérico a quien no tiene
                    // uno), el personal se registra igual, pero sin usuario.
                    $owner = User::withTrashed()
                        ->where('email', $row->email)
                        ->first();
                    if (!is_null($owner)) {
                        $now = Carbon::now();
                        $log[] = "$now | Correo $row->email ya registrado para otro usuario en la fila $row->id";
                        continue;
                    }
                    $user = User::create([
                        "person_id" => $person->id,
                        "email" => $row->email,
                        "password" => Hash::make($row->document_number),
                    ]);
                    $totalUsers++;

                    self::sendMail($row->names, $row->email, $row->document_number);
                }
                if ($user->trashed()) {
                    $user->restore();
                    $user->password = Hash::make($row->document_number);
                    $user->save();
                }
                $rol = Rol::select()
                    ->where('name', $row->type)
                    ->first();
                if ($rol && !$user->roles->contains($rol)) {
                    $user->roles()->attach($rol->id);
                }
            }
        }

        $date = Carbon::parse($import_detail->date);
        $now = Carbon::now();

        $log[] = "$now | 100% procesado";

        $import_detail->update([
            'progress' => $progress,
            'time_elapsed' => $date->diffInMinutes($now),
            'log' => json_encode($log),
        ]);

        $summary = [
            'Total de periodos importados' => $totalPeriods,
            'Total de personal importado' => $totalStaffs,
            'Total de usuarios creados' => $totalUsers,
        ];

        return $summary;
    }

    private static function importStudyProgram($import_detail, $rows)
    {
        $total = count($rows);
        $tenPercent = ceil($total / 10);

        $totalProductiveFamilies = 0;
        $totalStudyPrograms = 0;
        $totalStudyPlans = 0;

        $log = json_decode($import_detail->log);

        foreach ($rows as $index => $row) {

            if ($index % $tenPercent == 0) {
                $progress = round(($index / $total) * 100);

                if ($progress) {
                    $date = Carbon::parse($import_detail->date);
                    $now = Carbon::now();

                    $log[] = "$now | $progress% procesado";

                    $import_detail->update([
                        'progress' => $progress,
                        'time_elapsed' => $date->diffInMinutes($now),
                        'log' => json_encode($log),
                    ]);
                }
            }

            $productiveFamily = ProductiveFamily::select()
                ->where('name', $row->name_productive_family)
                ->first();
            if (is_null($productiveFamily)) {
                $productiveFamily = ProductiveFamily::create([
                    "name" => $row->name_productive_family,
                ]);
                $totalProductiveFamilies++;
            }

            $studyProgram = StudyProgram::select()
                ->where('name', $row->name_program)
                ->first();
            if (is_null($studyProgram)) {
                $studyProgram = StudyProgram::create([
                    "productive_family_id" => $productiveFamily->id,
                    "name" => $row->name_program,
                ]);
                $totalStudyPrograms++;
            }

            $studyPlan = StudyPlan::select()
                ->where('name', $row->name_plan)
                ->first();
            if (is_null($studyPlan)) {
                $studyPlan = StudyPlan::create([
                    "name" => $row->name_plan,
                    "type" => $row->type,
                ]);
                $totalStudyPlans++;
            }

            $programPlan = ProgramPlan::select()
                ->where('study_program_id', $studyProgram->id)
                ->where('study_plan_id', $studyPlan->id)
                ->first();
            if (is_null($programPlan)) {
                $programPlan = ProgramPlan::create([
                    "study_program_id" => $studyProgram->id,
                    "study_plan_id" => $studyPlan->id,
                ]);
            }
        }

        $date = Carbon::parse($import_detail->date);
        $now = Carbon::now();

        $log[] = "$now | 100% procesado";

        $import_detail->update([
            'progress' => $progress,
            'time_elapsed' => $date->diffInMinutes($now),
            'log' => json_encode($log),
        ]);

        $summary = [
            'Total de Familias Productivas importadas' => $totalProductiveFamilies,
            'Total de Programas de Estudio importados' => $totalStudyPrograms,
            'Total de Planes de Estudio importados' => $totalStudyPlans,
        ];

        return $summary;
    }

    private static function importRegistration($import_detail, $rows)
    {
        $total = count($rows);
        $tenPercent = ceil($total / 10);

        $totalCycles = 0;
        $totalStudents = 0;
        $totalUsers = 0;

        $studyPrograms = StudyProgram::select()->get()->keyBy('name');

        $log = json_decode($import_detail->log);

        foreach ($rows as $index => $row) {

            if ($index % $tenPercent == 0) {
                $progress = round(($index / $total) * 100);

                if ($progress) {
                    $date = Carbon::parse($import_detail->date);
                    $now = Carbon::now();

                    $log[] = "$now | $progress% procesado";

                    $import_detail->update([
                        'progress' => $progress,
                        'time_elapsed' => $date->diffInMinutes($now),
                        'log' => json_encode($log),
                    ]);
                }
            }

            $now = Carbon::now();

            $period = Period::select()
                ->where('name', $row->period)
                ->first();
            if (is_null($period)) {
                $log[] = "$now | Periodo $row->period no encontrado para la fila $row->id";
                continue;
            } else {
                $period->update([
                    "status" => $row->status_period,
                ]);
            }

            $studyProgram = $studyPrograms->get($row->name_program);
            if (is_null($studyProgram)) {
                $log[] = "$now | Programa de estudios $row->name_program no encontrado para la fila $row->id";
                continue;
            }

            $cycle = Cycle::select()
                ->where('name', $row->name_cycle)
                ->first();
            if (is_null($cycle)) {
                $cycle = Cycle::create([
                    "name" => $row->name_cycle,
                ]);
                $totalCycles++;
            }

            $person = Person::select()
                ->where('document_number', $row->document_number)
                ->first();
            if (is_null($person)) {
                $person = Person::create([
                    "document_type" => $row->document_type,
                    "document_number" => $row->document_number,
                    "names" => implode(' ', [
                        $row->last_name_one,
                        $row->last_name_two,
                        $row->name,
                    ]),
                    "phone" => $row->phone,
                    "email" => $row->email,
                    "birth_date" => Date::invertDateFormat($row->birth_date),
                    "sex" => $row->sex,
                    "native_language" => $row->native_language,
                ]);
            }

            $student = Student::select()
                ->where('period_id', $period->id)
                ->where('person_id', $person->id)
                ->where('study_program_id', $studyProgram->id)
                ->where('cycle_id', $cycle->id)
                ->first();
            if (is_null($student)) {
                $student = Student::create([
                    "period_id" => $period->id,
                    "person_id" => $person->id,
                    "study_program_id" => $studyProgram->id,
                    "cycle_id" => $cycle->id,
                    "status" => $row->registration_status,
                    "registration_date" => Date::invertDateTimeFormat($row->registration_date),
                ]);
                $totalStudents++;
            } else {
                $student->update([
                    "status" => $row->registration_status,
                    "registration_date" => Date::invertDateTimeFormat($row->registration_date),
                ]);
            }
            try {
                $user = User::select()
                    ->where('person_id', $person->id)
                    ->first();
                if (is_null($user)) {
                    //verificar si existe un usuario con el mismo correo
                    $user = User::select()
                        ->where('email', $row->email)
                        ->first();
                    if (!is_null($user)) {
                        $log[] = "$now | Correo $row->email ya registrado para otro usuario en la fila $row->id";
                        continue;
                    }
                    $user = User::create([
                        "person_id" => $person->id,
                        "email" => $row->email,
                        "password" => Hash::make($row->document_number),
                    ]);
                    $totalUsers++;
                    self::sendMail($row->name, $row->email, $row->document_number);
                }

                $rol = Rol::select()
                    ->where('name', 'ESTUDIANTE')
                    ->first();
                if (!$user->roles->contains($rol)) {
                    $user->roles()->attach($rol->id);
                }
            } catch (Exception $e) {
                // $log[] = "$now | Correo $row->email ya registrado para $person->names en la fila $row->id";
            }
        }

        $date = Carbon::parse($import_detail->date);
        $now = Carbon::now();

        $log[] = "$now | 100% procesado";

        $import_detail->update([
            'progress' => $progress,
            'time_elapsed' => $date->diffInMinutes($now),
            'log' => json_encode($log),
        ]);

        $summary = [
            'Total de ciclos importados' => $totalCycles,
            'Total de matriculas importadas' => $totalStudents,
            'Total de usuarios creados' => $totalUsers,
        ];

        return $summary;
    }

    private static function importEvaluation($import_detail, $rows)
    {
        $total = count($rows);
        $tenPercent = ceil($total / 10);

        $totalCourses = 0;
        $totalSections = 0;
        $totalShifts = 0;
        $totalClassrooms = 0;
        $totalParticipants = 0;
        $totalParticipantsUpdated = 0;

        $periods = Period::select()->get()->keyBy('name');
        $persons = Person::select()->get()->keyBy('document_number');
        $studyPrograms = StudyProgram::select()->get()->keyBy('name');
        $cycles = Cycle::select()->get()->keyBy('name');

        $log = json_decode($import_detail->log);

        foreach ($rows as $index => $row) {

            if ($index % $tenPercent == 0) {
                $progress = round(($index / $total) * 100);

                if ($progress) {
                    $date = Carbon::parse($import_detail->date);
                    $now = Carbon::now();

                    $log[] = "$now | $progress% procesado";

                    $import_detail->update([
                        'progress' => $progress,
                        'time_elapsed' => $date->diffInMinutes($now),
                        'log' => json_encode($log),
                    ]);
                }
            }

            $now = Carbon::now();

            $period = $periods->get($row->period);
            if (is_null($period)) {
                $log[] = "$now | Periodo $row->period no encontrado para la fila $row->id";
                continue;
            }

            $person = $persons->get($row->document_number);
            if (is_null($person)) {
                $log[] = "$now | Estudiante $row->document_number no encontrado para la fila $row->id";
                continue;
            }

            $studyProgram = $studyPrograms->get($row->name_program);
            if (is_null($studyProgram)) {
                $log[] = "$now | Programa de estudios $row->name_program no encontrado para la fila $row->id";
                continue;
            }

            $cycle = $cycles->get($row->name_cycle);
            if (is_null($cycle)) {
                $log[] = "$now |Ciclo $row->name_cycle no encontrado para la fila $row->id";
                continue;
            }

            $course = Course::select()
                ->where('study_program_id', $studyProgram->id)
                ->where('cycle_id', $cycle->id)
                ->where('name', $row->name_course)
                ->first();

            if (is_null($course)) {
                $course = Course::create([
                    "study_program_id" => $studyProgram->id,
                    "cycle_id" => $cycle->id,
                    "name" => $row->name_course,
                ]);
                $totalCourses++;
            }

            $section = Section::select()
                ->where('name', $row->name_section)
                ->first();
            if (is_null($section)) {
                $section = Section::create([
                    "name" => $row->name_section,
                ]);
                $totalSections++;
            }

            $shift = Shift::select()
                ->where('name', $row->name_shift)
                ->first();
            if (is_null($shift)) {
                $shift = Shift::create([
                    "name" => $row->name_shift,
                ]);
                $totalShifts++;
            }

            $classroom = Classroom::select()
                ->where('period_id', $period->id)
                ->where('course_id', $course->id)
                ->where('section_id', $section->id)
                ->where('shift_id', $shift->id)
                ->first();
            if (is_null($classroom)) {
                $classroom = Classroom::create([
                    "period_id" => $period->id,
                    "course_id" => $course->id,
                    "section_id" => $section->id,
                    "shift_id" => $shift->id,
                ]);
                $totalClassrooms++;
            }

            $participant = Participant::select()
                ->where('person_id', $person->id)
                ->where('classroom_id', $classroom->id)
                ->first();

            $score = is_numeric($row->score) ? (float) $row->score : 0.00;
            if (is_null($participant)) {
                $participant = Participant::create([
                    "person_id" => $person->id,
                    "classroom_id" => $classroom->id,
                    "score" => $score,
                ]);
                $totalParticipants++;
            } else {

                if ($participant->score != $score) {
                    $participant->update([
                        "score" => $score,
                    ]);
                    $totalParticipantsUpdated++;
                }
            }
        }

        $date = Carbon::parse($import_detail->date);
        $now = Carbon::now();

        $log[] = "$now | 100% procesado";

        $import_detail->update([
            'progress' => $progress,
            'time_elapsed' => $date->diffInMinutes($now),
            'log' => json_encode($log),
        ]);

        $summary = [
            'Total de cursos importados' => $totalCourses,
            'Total de secciones importadas' => $totalSections,
            'Total de turnos importados' => $totalShifts,
            'Total de clases importadas' => $totalClassrooms,
            'Total de participantes importados' => $totalParticipants,
            'Total de participantes actualizados' => $totalParticipantsUpdated,
        ];

        return $summary;
    }

    private static function sendMail($name, $email, $password)
    {
        $institutionName = SystemConfigurationHelper::getInstitutionName();
        // Antes se componía con el subdominio y el dominio de la base central, que
        // en un VPS por institución no existen: la importación fallaba con
        // «Parámetro no encontrado» al crear el primer usuario.
        $institutionUrl = CentralConfig::frontendUrl();

        $body = EmailBodyTemplate::credentials;
        $body = str_replace('{{institutionName}}', $institutionName, $body);
        $body = str_replace('{{institutionUrl}}', $institutionUrl, $body);
        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{email}}', $email, $body);
        $body = str_replace('{{password}}', $password, $body);

        $data = (object) [
            'subject' => 'Credenciales de acceso',
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }
}

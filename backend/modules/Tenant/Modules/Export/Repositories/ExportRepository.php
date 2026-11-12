<?php

namespace Modules\Tenant\Modules\Export\Repositories;

use Modules\Tenant\Modules\Classroom\Repositories\NoteReportRepository;
use Modules\Tenant\Modules\Export\Templates\EvaluationRecordTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Modules\Classroom\Repositories\AssistanceRepository;
use Modules\Tenant\Modules\Classroom\Repositories\ContentRepository;
use Modules\Tenant\Modules\Classroom\Repositories\EvaluationRepository;
use Modules\Tenant\Modules\Export\Templates\AbsencesTemplate;
use Modules\Tenant\Modules\Export\Templates\AttendanceConsolidatedTemplate;
use Modules\Tenant\Modules\Export\Templates\ConsolidatedRegistrationTemplate;
use Modules\Tenant\Modules\Export\Templates\ContentListTemplate;
use Modules\Tenant\Modules\Export\Templates\FinalNotesTemplate;
use Modules\Tenant\Modules\Export\Templates\HistoryTemplate;
use Modules\Tenant\Modules\Export\Templates\PaymentConceptsTemplate;
use Modules\Tenant\Modules\Export\Templates\RegistrationTemplate;
use Modules\Tenant\Modules\Management\Repositories\HistoryRepository;
use Modules\Tenant\Modules\Management\Repositories\RegistrationRepository;
use Modules\Tenant\Modules\Schedule\Repositories\ScheduleRepository;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Training\Repositories\TrainingAssistanceRepository;
use Modules\Tenant\Modules\Training\Repositories\TrainingContentRepository;
use Modules\Tenant\Modules\Training\Repositories\TrainingEvaluationRepository;
use Modules\Tenant\Modules\Treasury\Models\PaymentConcept;

class ExportRepository
{
    public static function execute(string $key, string $type, Request $request)
    {
        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionName = strtoupper($institutionName);

        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $applicationName = AdminSystemConfigurationHelper::getName();
        $applicationName = strtoupper($applicationName);

        $date = Carbon::now();
        $date = $date->isoFormat('dddd, D [de] MMMM [del] YYYY');

        $info = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'applicationName' => $applicationName,
            'date' => $date,
        ];

        switch ($key) {
            case 'registration':
                [$data, $template, $view] = self::exportRegistration($request, $info);
                break;
            case 'history':
                [$data, $template, $view] = self::exportHistory($request, $info);
                break;
            case 'schedule':
                [$data, $template, $view] = self::exportSchedule($request, $info);
                break;
            case 'consolidated-registration':
                [$data, $template, $view] = self::exportConsolidatedRegistration($request, $info);
                break;
            case 'absences':
                [$data, $template, $view] = self::exportAbsences($request, $info);
                break;
            case 'capacitation-absences':
                [$data, $template, $view] = self::exportCapacitationAbsences($request, $info);
                break;
            case 'attendance-consolidated':
                [$data, $template, $view] = self::exportAttendanceConsolidated($request, $info);
                break;
            case 'capacitation-attendance-consolidated':
                [$data, $template, $view] = self::exportCapacitationAttendanceConsolidated($request, $info);
                break;
            case 'consolidated-notes':
                [$data, $template, $view] = self::exportFinalNotes($request, $info);
                break;
            case 'capacitation-consolidated-notes':
                [$data, $template, $view] = self::exportCapacitationFinalNotes($request, $info);
                break;
            case 'content-list':
                [$data, $template, $view] = self::exportContentList($request, $info);
                break;
            case 'capacitation-content-list':
                [$data, $template, $view] = self::exportCapacitationContentList($request, $info);
            case 'payment-concepts':
                [$data, $template, $view] = self::exportPaymentConcepts($request, $info);
                break;
            case 'evaluation-record':
                [$data, $template, $view] = self::exportEvaluationRecord($request, $info);
                break;
            case 'evaluation-record-wide':
                [$data, $template, $view] = self::exportEvaluationRecordWide($request, $info);
                break;
            default:
                throw new Exception('Key no admitida');
        }

        function ensureNotNull($value, $errorMessage)
        {
            if (!$value) {
                throw new Exception($errorMessage);
            }
        }

        switch ($type) {
            case 'xlsx':
                ensureNotNull($template, 'Formato no disponible');
                $result = Generate::generateXlsx($template, $data);
                break;
            case 'pdf':
                ensureNotNull($view, 'Formato no disponible');
                $result = Generate::generatePdf($view, $data);
                break;
            default:
                throw new Exception('Tipo de exportación no admitido');
        }

        return $result;
    }

    /**
     * Registro de evaluación por capacidades (el auxiliar de evaluación). Los
     * datos y el control de acceso son los de la pantalla (note/report).
     */
    public static function exportEvaluationRecord(Request $request, object $info)
    {
        $result = NoteReportRepository::report($request);

        $filename = implode('_', [
            'registro_evaluacion',
            $result->classroomRows['course'] ?? '',
            bin2hex(random_bytes(3)),
        ]);

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => 'REGISTRO DE EVALUACIÓN',
            'heading' => 'REGISTRO DE CONTROL DE EVALUACIÓN',
            'filename' => $filename,
            'date' => $info->date,
            'protectSheet' => false,
            'classroomColumns' => $result->classroomColumns,
            'classroomRows' => $result->classroomRows,
            'capacities' => $result->capacities,
            'students' => $result->students,
        ];

        return [$data, EvaluationRecordTemplate::class, 'exports/evaluation-record'];
    }

    /** La misma hoja en A3 apaisado, con todas las columnas. Solo PDF. */
    public static function exportEvaluationRecordWide(Request $request, object $info)
    {
        [$data] = self::exportEvaluationRecord($request, $info);

        $data->paper = 'a3';
        $data->orientation = 'landscape';
        $data->filename = str_replace('registro_evaluacion', 'registro_evaluacion_completo', $data->filename);

        return [$data, null, 'exports/evaluation-record-wide'];
    }

    public static function exportRegistration(Request $request, object $info)
    {
        [$result, $person, $period] = RegistrationRepository::list($request);

        $title = 'LISTADO DE INSCRIPCIONES';

        $student = $person->document_number . ' - ' . $person->names;
        $period = $period->name;

        $columns = [
            'id' => '#',
            'course' => 'CURSO',
            'teacher' => 'PROFESOR',
            'shift' => 'TURNO',
            'status' => 'ESTADO',
        ];
        $columnsAligned = [
            'id',
            'shift',
            'status',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'student' => $student,
            'period' => $period,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = RegistrationTemplate::class;
        $view = 'exports/registration';

        return [$data, $template, $view];
    }

    public static function exportHistory(Request $request, object $info)
    {
        [$result, $person] = HistoryRepository::list($request);

        $title = 'HISTORIAL ACADÉMICO';

        $student = $person->document_number . ' - ' . $person->names;

        $columns = [
            'id' => '#',
            'name' => 'CURSO',
            'score' => 'NOTA',
        ];
        $columnsAligned = [
            'id',
            'score',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'student' => $student,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = HistoryTemplate::class;
        $view = 'exports/history';

        return [$data, $template, $view];
    }

    public static function exportSchedule(Request $request, object $info)
    {
        $result = ScheduleRepository::listByExport($request);

        $title = 'HORARIO DE CLASES';

        $days = SystemConfigurationHelper::getStudyDays();

        $hours = SystemConfigurationHelper::getStudyHours();
        $hour_start = Carbon::createFromFormat('H:i', $hours->start);
        $hour_end = Carbon::createFromFormat('H:i', $hours->end);

        $columns = ['hour' => 'Hora'] + $days;

        function generarColorSuave()
        {
            $r = mt_rand(160, 255);
            $g = mt_rand(160, 255);
            $b = mt_rand(160, 255);

            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }

        $daysMap = [];
        foreach ($result->list as $a) {
            $aName = $a['name'];
            $aDays = $a['days'];
            $color = generarColorSuave();

            foreach ($aDays as $b) {
                $bDay = $b['day'];
                $bHourStart = $b['hour_start'];
                $bHourEnd = $b['hour_end'];

                $add = [
                    'name' => $bHourStart . ' - ' . $bHourEnd . '<br>' . $aName,
                    'color' => $color,
                    'hour_start' => $bHourStart,
                    'hour_end' => $bHourEnd,
                ];

                $daysMap[$bDay][] = $add;
            }
        }

        $hoursMap = [];
        while ($hour_start->lte($hour_end)) {
            $hour = $hour_start->format('H:i');

            foreach ($days as $numberDay => $nameDay) {
                $hoursMap[$hour][$numberDay] = [];

                if (isset($daysMap[$numberDay])) {
                    foreach ($daysMap[$numberDay] as $c) {
                        if ($hour >= $c['hour_start'] && $hour <= $c['hour_end']) {
                            $hoursMap[$hour][$numberDay] = [$c['name'], $c['color']];
                        }
                    }
                }
            }

            $hour_start->addMinutes(30);
        }

        $rowsMap = [];
        foreach ($hoursMap as $keyHour => $itemDay) {
            if (array_filter($itemDay)) {
                $valueDay = array_values($itemDay);
                array_unshift($valueDay, [$keyHour, '#e0e0e0']);
                $rowsMap[] = $valueDay;
            }
        }

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'studyProgram' => $result->studyProgram,
            'rol' => $result->rol,
            'person' => $result->person,
            'period' => $result->period,
            'columns' => $columns,
            'rows' => $rowsMap,
        ];

        $template = null;
        $view = 'exports/schedule';

        return [$data, $template, $view];
    }

    public static function exportConsolidatedRegistration(Request $request, object $info)
    {
        [$result, $person] = RegistrationRepository::consolidated($request);

        $title = 'CONSOLIDADO DE MATRÍCULAS';

        $studentDocumentNumber = $person->document_number;
        $studentNames = $person->names;

        $columns = [
            'id' => '#',
            'period' => 'PERIODO',
            'cycle' => 'CICLO',
            'status' => 'ESTADO',
            'total_courses' => 'CURSOS INSCRITOS',
            'registration_date' => 'FECHA DE REGISTRO',
        ];
        $columnsAligned = [
            'id',
            'status',
            'total_courses',
            'registration_date',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'studentDocumentNumber' => $studentDocumentNumber,
            'studentNames' => $studentNames,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = ConsolidatedRegistrationTemplate::class;
        $view = 'exports/consolidated-registration';

        return [$data, $template, $view];
    }

    public static function exportAbsences(Request $request, object $info)
    {
        [$result, $classroom, $date] = AssistanceRepository::absencesPerDay($request);

        $title = 'ASISTENCIAS DEL ' . $date;

        $classroomColumns = [
            'study_program' => 'PROGRAMA DE ESTUDIOS',
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'study_program' => $classroom->course->studyProgram->name,
            'course' => $classroom->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'attended' => 'P',
            'absence' => 'F',
            'late' => 'T',
            'reason' => 'MOTIVO',
        ];
        $columnsAligned = [
            'id',
            'attended',
            'absence',
            'late',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = AbsencesTemplate::class;
        $view = 'exports/absences';

        return [$data, $template, $view];
    }
    public static function exportCapacitationAbsences(Request $request, object $info)
    {
        [$result, $training, $date] = TrainingAssistanceRepository::absencesPerDay($request);

        $title = 'ASISTENCIAS DEL ' . $date;

        $classroomColumns = [
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'attended' => 'P',
            'absence' => 'F',
            'late' => 'T',
            'reason' => 'MOTIVO',
        ];
        $columnsAligned = [
            'id',
            'attended',
            'absence',
            'late',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = AbsencesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportAttendanceConsolidated(Request $request, object $info)
    {
        [$result, $classroom, $participants] = AssistanceRepository::consolidated($request);

        $title = 'CONSOLIDADO DE ASISTENCIAS';

        $classroomColumns = [
            'study_program' => 'PROGRAMA DE ESTUDIOS',
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'study_program' => $classroom->course->studyProgram->name,
            'course' => $classroom->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'assistance' => 'ASISTENCIA',
        ];
        $columnsAligned = [
            'id',
            'assistance',
        ];
        $rows = $result->groupBy('date')->toArray();
        $participants = $participants->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
            'participants' => $participants,
        ];

        $template = AttendanceConsolidatedTemplate::class;
        $view = 'exports/attendance-consolidated';

        return [$data, $template, $view];
    }

    public static function exportCapacitationAttendanceConsolidated(Request $request, object $info)
    {
        [$result, $training, $participants] = TrainingAssistanceRepository::consolidated($request);

        $title = 'CONSOLIDADO DE ASISTENCIAS';

        $classroomColumns = [
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'assistance' => 'ASISTENCIA',
        ];
        $columnsAligned = [
            'id',
            'assistance',
        ];
        $rows = $result->groupBy('date')->toArray();
        $participants = $participants->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
            'participants' => $participants,
        ];

        $template = AttendanceConsolidatedTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportFinalNotes(Request $request, object $info)
    {
        $result = EvaluationRepository::finalNotes($request);

        $title = 'Plantilla';

        $columns = [
            'id' => 'NRO',
            'document_number' => 'CÓDIGO DE ALUMNO',
            'names' => 'ALUMNO',
            'score' => 'NOTA',
        ];
        $columnsAligned = [
            'id',
            'document_number',
            'score',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = FinalNotesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportPaymentConcepts(Request $request, object $info)
    {
        $concepts = PaymentConcept::with('denomination')
            ->where('deleted_at', null)
            ->get()
            ->map(function ($concept) {
                $concept['denomination_name'] = $concept->denomination->name;
                $concept['active'] = $concept->is_active ? 'ACTIVO' : 'INACTIVO';
                $concept['can_quotas'] = $concept->can_be_paid_in_quotas ? 'SI' : 'NO';
                $concept['include_enrollment'] = $concept->include_in_enrollment ? 'SI' : 'NO';
                return $concept;
            });

        $title = 'LISTADO DE CONCEPTOS DE PAGO';

        $columns = [
            'id' => '#',
            'code' => 'CÓDIGO',
            'name' => 'NOMBRE',
            'denomination_name' => 'DENOMINACIÓN',
            'gross_amount' => 'MONTO BRUTO',
            'igv_amount' => 'MONTO IGV',
            'net_amount' => 'MONTO NETO',
            'max_quotas' => 'CUOTAS MÁXIMAS',
            'active' => 'ESTADO',
            'can_quotas' => 'PUEDE PAGARSE EN CUOTAS',
            'include_enrollment' => 'INCLUIR EN MATRÍCULA',
        ];
        $columnsAligned = [
            'id',
            'gross_amount',
            'igv_amount',
            'net_amount',
            'is_active',
            'max_quotas',
            'can_quotas',
            'include_enrollment',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $concepts,
        ];

        $template = PaymentConceptsTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportContentList(Request $request, object $info)
    {
        [$result, $classroom] = ContentRepository::report($request);

        $title = 'LISTADO DE CONTENIDO';

        $classroomColumns = [
            'teacher' => 'DOCENTE',
            'course' => 'CURSO',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'teacher' => $classroom->teacher->person->name ?? '-',
            'course' => $classroom->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'content_group_title' => 'GRUPO DE CONTENIDO',
            'title' => 'NOMBRE',
            'type' => 'TIPO',
            'evaluation_group_title' => 'GRUPO DE EVALUACIÓN',
            'date' => 'FECHA DE PUBLICACIÓN',
            'date_limit' => 'FECHA DE ENTREGA',
            'status' => 'ESTADO',
        ];
        $columnsAligned = [
            'id',
            'type',
            'date',
            'date_limit',
            'status',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = ContentListTemplate::class;
        $view = 'exports/content-list';

        return [$data, $template, $view];
    }
    public static function exportCapacitationFinalNotes(Request $request, object $info)
    {
        $result = TrainingEvaluationRepository::finalNotes($request);

        $title = 'Plantilla';

        $columns = [
            'id' => 'NRO',
            'document_number' => 'CÓDIGO DE ALUMNO',
            'names' => 'ALUMNO',
            'score' => 'NOTA',
        ];
        $columnsAligned = [
            'id',
            'document_number',
            'score',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = FinalNotesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportCapacitationContentList(Request $request, object $info)
    {
        [$result, $training] = TrainingContentRepository::report($request);

        $title = 'LISTADO DE CONTENIDO';

        $classroomColumns = [
            'teacher' => 'DOCENTE',
            'course' => 'CURSO',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $trainingRows = [
            'teacher' => $training->teacher->person->name ?? '-',
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'content_group_title' => 'GRUPO DE CONTENIDO',
            'title' => 'NOMBRE',
            'type' => 'TIPO',
            'evaluation_group_title' => 'GRUPO DE EVALUACIÓN',
            'date' => 'FECHA DE PUBLICACIÓN',
            'date_limit' => 'FECHA DE ENTREGA',
            'status' => 'ESTADO',
        ];
        $columnsAligned = [
            'id',
            'type',
            'date',
            'date_limit',
            'status',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $trainingRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = ContentListTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }
}

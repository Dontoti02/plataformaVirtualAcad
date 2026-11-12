<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Enum\DaysOfWeek;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\NoteHelper;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Capacity;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\StudentCapacity;
use Modules\Tenant\Modules\Classroom\Models\StudentIndicator;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\Schedule\Models\Schedule;
use Modules\Tenant\Modules\User\Models\User;

/**
 * Registro de evaluación de una clase (pantalla y exportación) y nota de
 * recuperación por capacidad. Misma respuesta que la línea actual; aquí el
 * estudiante se identifica por su persona.
 */
class NoteReportRepository
{
    private const GROUP_EXCLUDED = 'Sílabo';

    public static function report(Request $request)
    {
        NoteHelper::validateReportRequest($request);

        $classroomId = (int) $request->input('classroom_id');
        $capacityId = $request->input('capacity_id');

        self::authorize($classroomId);

        $classroom = Classroom::with(['course.studyProgram', 'period', 'shift'])->findOrFail($classroomId);

        $capacities = Capacity::query()
            ->with(['indicators' => fn ($query) => $query->orderBy('order', 'asc')])
            ->where('classroom_id', $classroomId)
            ->when($capacityId, fn ($query) => $query->where('id', $capacityId))
            ->orderBy('order', 'asc')
            ->get();

        $indicatorIds = $capacities->pluck('indicators')->flatten()->pluck('id');

        $contents = Content::query()
            ->join('content_group', 'content.content_group_id', '=', 'content_group.id')
            ->where('content_group.classroom_id', $classroomId)
            ->whereNotIn('content_group.title', ['Documentos Pedagógicos', 'Sílabo'])
            ->whereIn('content.indicator_id', $indicatorIds)
            ->whereIn('content.type', ['task', 'evaluation'])
            ->orderBy('content_group.created_at', 'asc')
            ->orderBy('content_group.id', 'asc')
            ->orderBy('content.date_start', 'asc')
            ->orderBy('content.id', 'asc')
            ->get([
                'content.id',
                'content.title',
                'content.type',
                'content.indicator_id',
                'content_group.id as group_id',
                'content_group.title as group_title',
            ]);

        $students = Participant::query()
            ->join('person', 'participant.person_id', '=', 'person.id')
            ->where('participant.classroom_id', $classroomId)
            ->orderBy('person.names', 'asc')
            ->get([
                'participant.person_id',
                'person.names',
                'participant.score',
            ]);

        $personIds = $students->pluck('person_id');

        $scores = Answer::query()
            ->whereIn('content_id', $contents->pluck('id'))
            ->whereIn('person_id', $personIds)
            ->where('status', AnswerStatus::EVALUATED)
            ->get(['person_id', 'content_id', 'score'])
            ->groupBy('person_id')
            ->map(fn ($items) => $items->keyBy('content_id')->map(fn ($item) => $item->score));

        $notesByIndicator = StudentIndicator::query()
            ->whereIn('person_id', $personIds)
            ->whereIn('indicator_id', $indicatorIds)
            ->get()
            ->groupBy('person_id')
            ->map(fn ($items) => $items->keyBy('indicator_id')->map(fn ($item) => $item->score));

        $notesByCapacity = StudentCapacity::query()
            ->whereIn('person_id', $personIds)
            ->whereIn('capacity_id', $capacities->pluck('id'))
            ->get()
            ->groupBy('person_id')
            ->map(fn ($items) => $items->keyBy('capacity_id')->map(fn ($item) => [
                'score' => $item->score,
                'recover_score' => $item->recover_score,
            ]));

        $structure = self::buildStructure($capacities, $contents);

        return (object) [
            'classroomColumns' => [
                'study_program' => 'PROGRAMA DE ESTUDIOS',
                'module' => 'MÓDULO FORMATIVO',
                'course' => 'UNIDAD DIDÁCTICA',
                'period' => 'PERIODO ACADÉMICO',
                'shift' => 'TURNO',
                'schedule' => 'HORARIO',
            ],
            'classroomRows' => [
                'study_program' => $classroom->course?->studyProgram?->name ?? '-',
                // Esta línea no tiene módulos formativos.
                'module' => '-',
                'course' => $classroom->course?->name ?? '-',
                'period' => $classroom->period?->name ?? '-',
                'shift' => $classroom->shift?->name ?? '-',
                'schedule' => self::scheduleLabel($classroomId),
            ],
            'capacities' => $structure,
            'students' => self::buildStudents(
                $students,
                $structure,
                $scores,
                $notesByIndicator,
                $notesByCapacity,
                self::evaluatedPersons($classroomId)
            ),
        ];
    }

    /**
     * Pone (o quita, con valor vacío) la nota de recuperación de cada
     * estudiante en una capacidad y recalcula su nota final.
     */
    public static function saveRecovery(Request $request)
    {
        NoteHelper::validateRecoveryRequest($request);

        $notes = $request->input('notes');

        $capacities = Capacity::query()
            ->whereIn('id', array_column($notes, 'capacityId'))
            ->get()
            ->keyBy('id');

        foreach ($capacities->pluck('classroom_id')->unique() as $classroomId) {
            self::authorize($classroomId);
            PeriodHelper::validateByClassroom($classroomId);
        }

        $result = [];
        $recalculate = [];

        foreach ($notes as $note) {
            $classroomId = $capacities[$note['capacityId']]->classroom_id;

            $isParticipant = Participant::query()
                ->where('classroom_id', $classroomId)
                ->where('person_id', $note['studentId'])
                ->exists();

            if (!$isParticipant) {
                throw new Exception('El estudiante no pertenece a esta clase.');
            }

            $updated = StudentCapacity::query()
                ->where('person_id', $note['studentId'])
                ->where('capacity_id', $note['capacityId'])
                ->update(['recover_score' => $note['value'] ?? null]);

            $result[] = [
                'student_id' => $note['studentId'],
                'capacity_id' => $note['capacityId'],
                'value' => $note['value'] ?? null,
                'updated' => $updated > 0,
            ];

            $recalculate[$classroomId . '-' . $note['studentId']] = [$classroomId, (int) $note['studentId']];
        }

        // Cada estudiante con su nota final recalculada (en la línea actual solo
        // se hacía con el último de la lista).
        foreach ($recalculate as [$classroomId, $personId]) {
            CapacityScoreRepository::recalculate($classroomId, $personId);
        }

        return $result;
    }

    private static function evaluatedPersons(int $classroomId)
    {
        return Answer::query()
            ->join('content', 'answer.content_id', '=', 'content.id')
            ->join('content_group', 'content.content_group_id', '=', 'content_group.id')
            ->where('content_group.classroom_id', $classroomId)
            ->whereNotNull('content.indicator_id')
            ->where('answer.status', AnswerStatus::EVALUATED)
            ->distinct()
            ->pluck('answer.person_id')
            ->flip();
    }

    private static function buildStructure($capacities, $contents): array
    {
        $structure = [];

        foreach ($capacities as $capacityIndex => $capacity) {
            $indicators = [];

            foreach ($capacity->indicators as $indicatorIndex => $indicator) {
                $weeks = [];

                $indicatorContents = $contents->where('indicator_id', $indicator->id);

                foreach ($indicatorContents->groupBy('group_id') as $groupContents) {
                    $groupTitle = $groupContents->first()->group_title;

                    $weeks[] = [
                        'label' => self::weekLabel($groupTitle),
                        'title' => $groupTitle,
                        'span' => $groupContents->count(),
                        'contents' => $groupContents
                            ->map(fn ($content) => [
                                'id' => $content->id,
                                'label' => $content->type == 'evaluation' ? 'EV' : 'T',
                                'title' => $content->title,
                            ])
                            ->values()
                            ->all(),
                    ];
                }

                $indicators[] = [
                    'id' => $indicator->id,
                    'label' => 'INDICADOR DE LOGRO N° ' . ($indicatorIndex + 1),
                    'name' => $indicator->name,
                    'span' => $indicatorContents->count() + 2,
                    'weeks' => $weeks,
                    'contents' => $indicatorContents->pluck('id')->values()->all(),
                ];
            }

            $structure[] = [
                'id' => $capacity->id,
                'short' => 'C' . ($capacityIndex + 1),
                'label' => 'CAPACIDAD ' . ($capacityIndex + 1),
                'name' => $capacity->name,
                'span' => array_sum(array_column($indicators, 'span')),
                'indicators' => $indicators,
            ];
        }

        return $structure;
    }

    private static function buildStudents($students, array $structure, $scores, $notesByIndicator, $notesByCapacity, $evaluatedPersons): array
    {
        $rows = [];

        foreach ($students->values() as $index => $student) {
            $studentScores = $scores->get($student->person_id);
            $studentIndicators = $notesByIndicator->get($student->person_id);
            $studentCapacities = $notesByCapacity->get($student->person_id);

            $contentCells = [];
            $indicatorCells = [];
            $capacityCells = [];
            $capacityRecoverScores = [];

            foreach ($structure as $capacity) {
                $capacityEvaluated = false;

                foreach ($capacity['indicators'] as $indicator) {
                    $indicatorEvaluated = false;

                    foreach ($indicator['contents'] as $contentId) {
                        $score = $studentScores ? $studentScores->get($contentId) : null;
                        $contentCells[$contentId] = self::formatScore($score);

                        if ($score !== null) {
                            $indicatorEvaluated = true;
                            $capacityEvaluated = true;
                        }
                    }

                    $indicatorCells[$indicator['id']] = $indicatorEvaluated
                        ? self::formatScore($studentIndicators ? $studentIndicators->get($indicator['id']) : null)
                        : '-';
                }

                $capacityNote = $studentCapacities?->get($capacity['id']);

                $capacityCells[$capacity['id']] = $capacityEvaluated
                    ? self::formatScore($capacityNote['score'] ?? null)
                    : '-';
                $capacityRecoverScores[$capacity['id']] = self::formatScore($capacityNote['recover_score'] ?? null);
            }

            $rows[] = [
                // La persona: es lo que save-recovery espera como studentId.
                'id' => $student->person_id,
                'number' => $index + 1,
                'name' => $student->names,
                'contents' => $contentCells,
                'indicators' => $indicatorCells,
                'capacities' => $capacityCells,
                'capacityRecoverScores' => $capacityRecoverScores,
                'final' => $evaluatedPersons->has($student->person_id)
                    ? self::formatScore($student->score)
                    : '-',
            ];
        }

        return $rows;
    }

    private static function weekLabel(string $title): string
    {
        if (preg_match('/\d+/', $title, $matches)) {
            return 'S' . (int) $matches[0];
        }

        $words = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) > 1) {
            $initials = array_map(fn ($word) => mb_substr($word, 0, 1), array_slice($words, 0, 3));

            return mb_strtoupper(implode('', $initials));
        }

        return mb_strtoupper(mb_substr($title, 0, 3));
    }

    private static function formatScore($value): string
    {
        if ($value === null) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private static function scheduleLabel(int $classroomId): string
    {
        $schedules = Schedule::query()
            ->where('classroom_id', $classroomId)
            ->orderBy('day', 'asc')
            ->orderBy('hour_start', 'asc')
            ->get();

        if ($schedules->isEmpty()) {
            return '-';
        }

        return $schedules
            ->map(function ($schedule) {
                $day = DaysOfWeek::DAYS[(string) $schedule->day] ?? '';

                return trim("$day $schedule->hour_start - $schedule->hour_end");
            })
            ->implode(' / ');
    }

    private static function authorize(int $classroomId): void
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $classroomId);
    }
}

<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Enums\AnswerStatus;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Helpers\NoteHelper;
use Modules\Tenant\Modules\Classroom\Models\Answer;
use Modules\Tenant\Modules\Classroom\Models\Capacity;
use Modules\Tenant\Modules\Classroom\Models\Content;
use Modules\Tenant\Modules\Classroom\Models\Indicator;
use Modules\Tenant\Modules\Classroom\Models\Participant;
use Modules\Tenant\Modules\Classroom\Models\StudentCapacity;
use Modules\Tenant\Modules\Classroom\Models\StudentIndicator;
use Modules\Tenant\Modules\User\Models\User;

/**
 * Tabla de notas de una clase, de una capacidad o de un indicador. Misma
 * respuesta que la línea actual (headers + body), con dos diferencias:
 * - pide acceso a la clase (allí no se comprobaba);
 * - cada nota va en la columna de su capacidad/indicador/contenido. Allí se
 *   añadían en orden y, si a un estudiante le faltaba una, las siguientes se
 *   corrían de columna.
 */
class NoteRepository
{
    public static function list(Request $request)
    {
        NoteHelper::validateRequest($request);

        $indicatorId = $request->input('indicator_id');
        $capacityId = $request->input('capacity_id');

        $headers = [
            ['label' => 'N°', 'placeholder' => null],
            ['label' => 'ESTUDIANTE', 'placeholder' => null],
        ];

        if ($indicatorId) {
            $classroomId = Indicator::byKey('id', $indicatorId)->capacity->classroom_id;
            self::authorize($classroomId);
            [$columns, $scores] = self::byIndicator((int) $indicatorId);
        } elseif ($capacityId) {
            $classroomId = Capacity::byKey('id', $capacityId)->classroom_id;
            self::authorize($classroomId);
            [$columns, $scores] = self::byCapacity((int) $capacityId);
        } else {
            $classroomId = (int) $request->input('classroom_id');
            self::authorize($classroomId);
            [$columns, $scores] = self::byClassroom($classroomId);
        }

        foreach ($columns as $column) {
            $headers[] = ['label' => $column['label'], 'placeholder' => $column['placeholder']];
        }

        $headers[] = ['label' => 'PROMEDIO FINAL', 'placeholder' => null];

        $body = [];

        foreach (self::students($classroomId) as $student) {
            $row = [$student->person_id, $student->names];

            foreach ($columns as $column) {
                $row[] = $scores[$student->person_id][$column['id']] ?? null;
            }

            $row[] = (float) $student->score;

            $body[] = $row;
        }

        return [
            'headers' => $headers,
            'body' => $body,
        ];
    }

    private static function byClassroom(int $classroomId): array
    {
        $capacities = Capacity::query()
            ->where('classroom_id', $classroomId)
            ->orderBy('order', 'asc')
            ->get();

        $columns = $capacities->map(fn ($item) => [
            'id' => $item->id,
            'label' => 'C' . $item->order,
            'placeholder' => $item->weight
                ? $item->name . ' (' . $item->weight * 100 . '%)'
                : $item->name,
        ])->all();

        $scores = [];

        $notes = StudentCapacity::query()
            ->whereIn('capacity_id', $capacities->pluck('id'))
            ->get();

        foreach ($notes as $note) {
            $scores[$note->person_id][$note->capacity_id] = $note->recover_score ?? $note->score;
        }

        return [$columns, $scores];
    }

    private static function byCapacity(int $capacityId): array
    {
        $indicators = Indicator::query()
            ->where('capacity_id', $capacityId)
            ->orderBy('order', 'asc')
            ->get();

        $columns = $indicators->map(fn ($item) => [
            'id' => $item->id,
            'label' => 'I' . $item->order,
            'placeholder' => $item->name,
        ])->all();

        $scores = [];

        $notes = StudentIndicator::query()
            ->whereIn('indicator_id', $indicators->pluck('id'))
            ->get();

        foreach ($notes as $note) {
            $scores[$note->person_id][$note->indicator_id] = $note->score;
        }

        return [$columns, $scores];
    }

    private static function byIndicator(int $indicatorId): array
    {
        $contents = Content::query()
            ->where('indicator_id', $indicatorId)
            ->whereIn('type', ['task', 'evaluation'])
            ->orderBy('date_start', 'asc')
            ->get();

        $columns = $contents->values()->map(fn ($item, $index) => [
            'id' => $item->id,
            'label' => strtoupper($item->type[0]) . ($index + 1),
            'placeholder' => $item->title . ($item->date_start
                ? ' (' . Carbon::parse($item->date_start)->format('d/m/Y') . ')'
                : ''),
        ])->all();

        $scores = [];

        $answers = Answer::query()
            ->whereIn('content_id', $contents->pluck('id'))
            ->where('status', AnswerStatus::EVALUATED)
            ->get(['person_id', 'content_id', 'score']);

        foreach ($answers as $answer) {
            $scores[$answer->person_id][$answer->content_id] = (float) $answer->score;
        }

        return [$columns, $scores];
    }

    private static function students(int $classroomId)
    {
        return Participant::query()
            ->join('person', 'participant.person_id', 'person.id')
            ->where('participant.classroom_id', $classroomId)
            ->orderBy('person.names', 'asc')
            ->get([
                'participant.person_id',
                'person.names',
                'participant.score',
            ]);
    }

    private static function authorize(int $classroomId): void
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $classroomId);
    }
}

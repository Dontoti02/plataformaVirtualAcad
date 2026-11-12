<?php

namespace Modules\Tenant\Modules\Classroom\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Classroom\Helpers\CapacityHelper;
use Modules\Tenant\Modules\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Modules\Classroom\Models\Capacity;
use Modules\Tenant\Modules\Classroom\Models\Classroom;
use Modules\Tenant\Modules\Classroom\Models\Indicator;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Models\User;

/**
 * Capacidades e indicadores de una clase. Mismas operaciones y respuestas que
 * la línea actual (Packages/Capacity), para que su frontend sirva aquí.
 *
 * En vez de «clase cerrada» (que esta línea no tiene) se bloquea cuando el
 * periodo de la clase ha terminado, como hacían las competencias.
 */
class CapacityRepository
{
    public static function list(int $classroomId)
    {
        Classroom::byKey('id', $classroomId);

        self::authorize($classroomId, false);

        return Capacity::query()
            ->with(['indicators' => fn ($query) => $query->orderBy('order', 'asc')])
            ->where('classroom_id', $classroomId)
            ->orderBy('order', 'asc')
            ->get();
    }

    public static function create(Request $request)
    {
        CapacityHelper::validateCreateOrUpdateRequest($request);

        $classroomId = (int) $request->input('classroom_id');
        $name = trim($request->input('name'));

        self::authorizeChange($classroomId);
        self::validateUniqueName($classroomId, $name);

        return Capacity::create([
            'classroom_id' => $classroomId,
            'name' => $name,
            'order' => self::nextCapacityOrder($classroomId),
        ]);
    }

    public static function createWithIndicators(Request $request)
    {
        CapacityHelper::validateSaveRequest($request);

        $classroomId = (int) $request->input('classroom_id');
        $name = trim($request->input('name'));
        $indicators = array_values($request->input('indicators'));

        self::authorizeChange($classroomId);
        self::validateUniqueName($classroomId, $name);

        $capacity = Capacity::create([
            'classroom_id' => $classroomId,
            'name' => $name,
            'order' => self::nextCapacityOrder($classroomId),
        ]);

        foreach ($indicators as $index => $indicator) {
            Indicator::create([
                'capacity_id' => $capacity->id,
                'name' => trim($indicator['name']),
                'order' => $index + 1,
            ]);
        }

        return $capacity->load(['indicators' => fn ($query) => $query->orderBy('order', 'asc')]);
    }

    public static function update(int $id, Request $request)
    {
        $capacity = Capacity::byKey('id', $id);

        CapacityHelper::validateCreateOrUpdateRequest($request);

        $name = trim($request->input('name'));

        self::authorizeChange($capacity->classroom_id);
        self::validateUniqueName($capacity->classroom_id, $name, $id);

        $capacity->update(['name' => $name]);

        return $capacity;
    }

    public static function updateWithIndicators(int $id, Request $request)
    {
        $capacity = Capacity::byKey('id', $id);

        CapacityHelper::validateSaveRequest($request, true);

        $name = trim($request->input('name'));
        $indicators = array_values($request->input('indicators'));

        self::authorizeChange($capacity->classroom_id);
        self::validateUniqueName($capacity->classroom_id, $name, $id);

        $capacity->update(['name' => $name]);

        $currentIndicators = Indicator::query()
            ->where('capacity_id', $id)
            ->get()
            ->keyBy('id');

        $keptIds = [];

        foreach ($indicators as $index => $indicator) {
            $indicatorId = isset($indicator['id']) ? (int) $indicator['id'] : null;
            $indicatorName = trim($indicator['name']);
            $order = $index + 1;

            if ($indicatorId === null) {
                Indicator::create([
                    'capacity_id' => $id,
                    'name' => $indicatorName,
                    'order' => $order,
                ]);

                continue;
            }

            if (!$currentIndicators->has($indicatorId)) {
                throw new Exception('El indicador que intentas actualizar no pertenece a esta capacidad.');
            }

            $currentIndicators->get($indicatorId)->update([
                'name' => $indicatorName,
                'order' => $order,
            ]);

            $keptIds[] = $indicatorId;
        }

        $removedIndicators = $currentIndicators->reject(fn ($item) => in_array($item->id, $keptIds));

        foreach ($removedIndicators as $indicator) {
            self::validateIndicatorRemovable($indicator, '"' . $indicator->name . '" ');
            $indicator->delete();
        }

        return $capacity->load(['indicators' => fn ($query) => $query->orderBy('order', 'asc')]);
    }

    public static function sort(int $id, int $position)
    {
        $capacity = Capacity::byKey('id', $id);

        self::authorizeChange($capacity->classroom_id);

        $capacities = Capacity::query()
            ->where('classroom_id', $capacity->classroom_id)
            ->orderBy('order', 'asc')
            ->get();

        self::reorder($capacities, $capacity, $position);

        return 'Capacidad reordenada correctamente.';
    }

    public static function delete(int $id)
    {
        $capacity = Capacity::byKey('id', $id);

        self::authorizeChange($capacity->classroom_id);

        if ($capacity->indicators()->exists()) {
            throw new Exception('No se puede eliminar una capacidad que tiene indicadores asociados.');
        }

        if ($capacity->studentCapacities()->exists()) {
            throw new Exception('No se puede eliminar una capacidad que tiene capacidades de estudiantes asociadas.');
        }

        $capacity->delete();

        return 'Capacidad eliminada correctamente.';
    }

    public static function createIndicator(Request $request)
    {
        CapacityHelper::validateCreateOrUpdateIndicatorRequest($request);

        $capacity = Capacity::byKey('id', (int) $request->input('capacity_id'));
        $name = trim($request->input('name'));

        self::authorizeChange($capacity->classroom_id);
        self::validateUniqueIndicatorName($capacity->id, $name);

        $lastOrder = Indicator::query()
            ->where('capacity_id', $capacity->id)
            ->max('order');

        return Indicator::create([
            'capacity_id' => $capacity->id,
            'name' => $name,
            'order' => $lastOrder ? $lastOrder + 1 : 1,
        ]);
    }

    public static function updateIndicator(int $id, Request $request)
    {
        $indicator = Indicator::byKey('id', $id);

        CapacityHelper::validateCreateOrUpdateIndicatorRequest($request);

        $name = trim($request->input('name'));

        self::authorizeChange($indicator->capacity->classroom_id);
        self::validateUniqueIndicatorName($indicator->capacity_id, $name, $id);

        $indicator->update(['name' => $name]);

        return $indicator;
    }

    public static function sortIndicator(int $id, int $position)
    {
        $indicator = Indicator::byKey('id', $id);

        self::authorizeChange($indicator->capacity->classroom_id);

        $indicators = Indicator::query()
            ->where('capacity_id', $indicator->capacity_id)
            ->orderBy('order', 'asc')
            ->get();

        self::reorder($indicators, $indicator, $position);

        return 'Indicador reordenado correctamente.';
    }

    public static function deleteIndicator(int $id)
    {
        $indicator = Indicator::byKey('id', $id);

        self::authorizeChange($indicator->capacity->classroom_id);
        self::validateIndicatorRemovable($indicator);

        // Una capacidad nunca se queda sin indicador: sin él no hay dónde
        // colgar tareas y evaluaciones.
        $remaining = Indicator::query()
            ->where('capacity_id', $indicator->capacity_id)
            ->where('id', '!=', $id)
            ->exists();

        if (!$remaining) {
            throw new Exception('Una capacidad debe tener al menos un indicador.');
        }

        $indicator->delete();

        return 'Indicador eliminado correctamente.';
    }

    /**
     * Ver: docente, secretaría, administración y estudiantes de la clase.
     * Modificar: docente, secretaría y administración, con el periodo abierto.
     */
    private static function authorize(int $classroomId, bool $write = true): void
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        if ($write) {
            ClassroomHelper::validateASTAccess($user->person_id, $user->rol_id, $classroomId);
        } else {
            ClassroomHelper::validateASTSAccess($user->person_id, $user->rol_id, $classroomId);
        }
    }

    private static function authorizeChange(int $classroomId): void
    {
        self::authorize($classroomId);
        PeriodHelper::validateByClassroom($classroomId);
    }

    private static function validateUniqueName(int $classroomId, string $name, ?int $exceptId = null): void
    {
        $exists = Capacity::query()
            ->where('classroom_id', $classroomId)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw new Exception('Ya existe una capacidad con el mismo nombre en esta clase.');
        }
    }

    private static function validateUniqueIndicatorName(int $capacityId, string $name, ?int $exceptId = null): void
    {
        $exists = Indicator::query()
            ->where('capacity_id', $capacityId)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw new Exception('Ya existe un indicador con el mismo nombre en esta capacidad.');
        }
    }

    private static function validateIndicatorRemovable(Indicator $indicator, string $label = ''): void
    {
        if ($indicator->contents()->exists()) {
            throw new Exception('No se puede eliminar el indicador ' . $label . 'porque tiene contenidos asociados.');
        }

        if ($indicator->studentIndicators()->exists()) {
            throw new Exception('No se puede eliminar el indicador ' . $label . 'porque tiene notas de estudiantes.');
        }
    }

    private static function nextCapacityOrder(int $classroomId): int
    {
        $lastOrder = Capacity::query()
            ->where('classroom_id', $classroomId)
            ->max('order');

        return $lastOrder ? $lastOrder + 1 : 1;
    }

    private static function reorder($items, $item, int $position): void
    {
        if ($position < 1 || $position > $items->count()) {
            throw new Exception('La posición está fuera de rango.');
        }

        $ordered = $items->reject(fn ($current) => $current->id === $item->id)->values();
        $ordered->splice($position - 1, 0, [$item]);

        foreach ($ordered as $index => $current) {
            $current->update(['order' => $index + 1]);
        }
    }
}

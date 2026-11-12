<?php

namespace Modules\Tenant\Modules\JobOpportunities\MasterTable\Controllers;

use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\JobOpportunities\MasterTable\Enums\MasterTablesEnum;

class MasterTableController extends Controller
{
  protected function resolveModel(string $tableKey): ?Model
  {
    $modelClass = MasterTablesEnum::MAP[$tableKey] ?? null;

    if (!class_exists($modelClass)) {
      abort(404, 'Tabla maestra no encontrada');
    }

    return new $modelClass;
  }

  public function index(string $table)
  {
    $model = $this->resolveModel($table);
    return Response::success($model->orderBy('name')->get());
  }

  public function store(Request $request, string $table)
  {
    try {
      $model = $this->resolveModel($table);
      $data = $request->validate([
        'name' => 'required|string|max:255',
      ]);
      $created = $model->create($data);
      return Response::success($created);
    } catch (Exception $e) {
      return Response::error('Error al crear registro en tabla maestra: ' . $e->getMessage());
    }
  }

  public function update(Request $request, string $table, int $id)
  {
    try {
      $model = $this->resolveModel($table)->findOrFail($id);
      $data = $request->validate([
        'name' => 'required|string|max:255',
      ]);
      $model->update($data);
      return Response::success($model);
    } catch (Exception $e) {
      return Response::error('Error al actualizar registro en tabla maestra: ' . $e->getMessage());
    }
  }

  public function destroy(string $table, int $id)
  {
    $model = $this->resolveModel($table)->findOrFail($id);

    if ($model->offers()->count() > 0) {
      return Response::error('No se puede eliminar, existen ofertas asociadas a este registro.');
    }
    $model->delete();

    return Response::success([], 'Eliminado correctamente');
  }
}

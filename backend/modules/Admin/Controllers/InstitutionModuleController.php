<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Admin\Enum\InstitutionModulesEnum;
use Modules\Admin\Models\InstitutionModule;
use Modules\Shared\Utils\Response;

class InstitutionModuleController extends Controller
{
  public function index(int $institucionId)
  {

    $baseModules = InstitutionModulesEnum::CONFIG;

    $existing = InstitutionModule::where('institution_id', $institucionId)->pluck('module_key')->toArray();

    $faltantes = collect($baseModules)->filter(fn($m) => !in_array($m['module_key'], $existing));

    foreach ($faltantes as $module) {
      InstitutionModule::create([
        'institution_id' => $institucionId,
        'name' => $module['name'],
        'module_key' => $module['module_key'],
        'is_active' => $module['is_active'],
      ]);
    }

    $modules = InstitutionModule::where('institution_id', $institucionId)->get();

    return Response::success($modules);
  }

  public function toggle(Request $request, $institutionId)
  {
    $data = $request->validate([
      'moduleKey' => 'required|string',
      'isActive' => 'required|boolean',
    ]);

    $module = InstitutionModule::updateOrCreate(
      [
        'institution_id' => $institutionId,
        'module_key' => $data['moduleKey'],
      ],
      [
        'is_active' => $data['isActive'],
        'end_date' => null,
        'start_date' => null
      ]
    );

    return Response::success("Módulo {$module->module_key} actualizado correctamente");
  }

  public function updateDates(Request $request, $institutionId)
  {
    $data = $request->validate([
      'moduleKey' => 'required|string',
      'startDate' => 'nullable|date',
      'endDate' => 'nullable|date',
    ]);

    $module = InstitutionModule::where('institution_id', $institutionId)
      ->where('module_key', $data['moduleKey'])
      ->firstOrFail();

    $module->update([
      'start_date' => $data['startDate'],
      'end_date' => $data['endDate'],
    ]);

    return Response::success("Fechas del módulo {$module->module_key} actualizadas correctamente");
  }
}

<?php

namespace Modules\Tenant\Modules\Training\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Training\Helpers\TrainingReportHelper;
use Modules\Tenant\Modules\Training\Models\Training;
use Modules\Tenant\Modules\Training\Models\TrainingStatus;
use Modules\Tenant\Modules\Training\Templates\ListTemplate;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;


class TrainingReportRepository
{
    public static function filters()
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        $status = TrainingStatus::all();

        $result = [
            'status' => $status,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingReportHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $trainingStatusId = $request->input('training_status_id');

        $trainings = Training::select([
            'training.id',
            'training.name',
            'training.start_date',
            'training.end_date',
            DB::raw('(
                SELECT COUNT(*) 
                FROM training_participant 
                WHERE training_participant.training_id = training.id
            ) as enrolled'),
            DB::raw('0 as suspended'),
            DB::raw('0 as approved'),
            DB::raw('0 as failed'),
            DB::raw('0 as sessions'),
            'training_status.name as status_name',
        ])
            ->join('training_status', 'training.training_status_id', '=', 'training_status.id')
            ->when($search, function ($query) use ($search) {
                $query->where('training.name', 'like', "%{$search}%");
            })
            ->when($trainingStatusId, function ($query) use ($trainingStatusId) {
                $query->where('training.training_status_id', $trainingStatusId);
            })
            ->orderBy('training.name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'summary' => [
                'enrolled' => 0,
                'certificates' => 0,
                'failed' => 0,
                'suspended' => 0,
            ],
            'total' => $trainings->total(),
            'data' => $trainings->items(),
        ];

        return $result;
    }

    public static function listDownload(Request $request)
    {
        User::authenticated(RolTenant::TRAINING_ADMINISTRADOR);

        TrainingReportHelper::validateListDownloadRequest($request);

        $search = $request->input('search');
        $trainingStatusId = $request->input('training_status_id');

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $trainings = Training::select([
            'training.id',
            'training.name',
            'training.start_date',
            'training.end_date',
            DB::raw('(
                SELECT COUNT(*) 
                FROM training_participant 
                WHERE training_participant.training_id = training.id
            ) as enrolled'),
            DB::raw('0 as suspended'),
            DB::raw('0 as approved'),
            DB::raw('0 as failed'),
            DB::raw('0 as sessions'),
            'training_status.name as status_name',
        ])
            ->join('training_status', 'training.training_status_id', '=', 'training_status.id')
            ->when($search, function ($query) use ($search) {
                $query->where('training.name', 'like', "%{$search}%");
            })
            ->when($trainingStatusId, function ($query) use ($trainingStatusId) {
                $query->where('training.training_status_id', $trainingStatusId);
            })
            ->orderBy('training.name', 'asc')
            ->get();

        $rows = [];
        foreach ($trainings as $indexItem => $item) {
            $rows[] = [
                'id' => $indexItem + 1,
                'name' => $item->name,
                'date' => Carbon::parse($item->start_date)->format('d M Y') . ' - ' . Carbon::parse($item->end_date)->format('d M Y'),
                'enrolled' => $item->enrolled,
                'suspended' => $item->suspended,
                'approved' => $item->approved,
                'failed' => $item->failed,
                'sessions' => $item->sessions,
                'status' => $item->status_name,
            ];
        }

        if (count($rows) === 0) {
            throw new Exception('¡No hay datos para exportar!');
        }

        $filters = [
            'BÚSQUEDA' => $search ?? null,
            'ESTADO' => $trainingStatusId ? TrainingStatus::findOrFail($trainingStatusId)->name : null,
        ];

        $columns = [
            'id' => '#',
            'name' => 'CAPACITACIÓN',
            'date' => 'FECHA',
            'enrolled' => 'INSCRITOS',
            'suspended' => 'SUSPENDIDOS',
            'approved' => 'APROBADOS',
            'failed' => 'DESAPROBADOS',
            'sessions' => 'SESIONES',
            'status' => 'ESTADO',
        ];

        $columnsAligned = [
            'id',
            'date',
            'enrolled',
            'suspended',
            'approved',
            'failed',
            'sessions',
            'status',
        ];

        $data = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'title' => 'CAPACITACIONES',
            'date' => 'FECHA: ' . Carbon::now()->format('d/m/Y h:i A'),
            'footer' => 'Total de capacitaciones: ' . count($rows),
            'filters' => $filters,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = ListTemplate::class;

        $result = Generate::generateXlsx($template, $data);

        return $result;
    }
}

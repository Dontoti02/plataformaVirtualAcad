<?php

namespace Modules\Admin\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Helpers\InstitutionHelper;
use Modules\Admin\Helpers\SystemConfigurationHelper;
use Modules\Admin\Models\Domain;
use Modules\Admin\Models\Institution;
use Modules\Admin\Models\InstitutionStorage;
use Modules\Admin\Models\Tenant;
use Modules\Shared\Models\Ubigeo;
use Modules\Shared\Services\FileSystemService;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;

class InstitutionRepository
{
    public static function config()
    {
        $ubigeo = Ubigeo::select([
            'inei',
            'reniec',
            'department',
            'province',
            'district'
        ])->get();

        $domain = SystemConfigurationHelper::getDomain();

        $result = [
            'ubigeo' => $ubigeo,
            'domain' => $domain
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort');
        $page = $request->input('page', 1);
        $perPage = $request->input('items', 10);

        $query = Institution::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('ruc', 'like', '%' . $search . '%')
                    ->orWhere('modular_code', 'like', '%' . $search . '%');
            });
        }

        if (!isset($sort)) {
            $query->orderBy('created_at', 'DESC');
        }

        if ($sort) {
            usort($sort, function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });

            foreach ($sort as $order) {
                $query->orderBy($order['column'], $order['direction']);
            }
        }

        $query->with('domain');

        $pagination = $query->paginate($perPage, ['*'], 'page', $page);

        $now = Carbon::now();

        $items = [];
        foreach ($pagination->items() as $item) {
            $is_active = (bool) $item->is_active;

            $start_date = Carbon::parse($item->start_date);

            if ($now >= $item->start_date) {
                $is_active = true;
            }

            if ($item->end_date) {
                $end_date = Carbon::parse($item->end_date);

                $is_active = $now >= $start_date && $now <= $end_date;
            }

            if ($is_active != $item->is_active) {
                Institution::byKey('id', $item->id)
                    ->update([
                        'is_active' => $is_active,
                    ]);
            }

            $items[] = [
                'id' => $item->id,
                'modular_code' => $item->modular_code,
                'ruc' => $item->ruc,
                'name' => $item->name,
                'type_management' => $item->type_management,
                'department' => $item->department,
                'province' => $item->province,
                'district' =>  $item->district,
                'address' => $item->address,
                'logo' => $item->logo,
                'is_active' => $is_active,
                'start_date' =>$item->start_date,
                'end_date' => $item->end_date,
                'subdomain' => $item->domain->domain,
                'created_at' => $item->created_at,
            ];
        }

        return  [
            'items' => $items,
            'pagination' => [
                'page' => $pagination->currentPage(),
                'pages' => $pagination->lastPage(),
                'total' =>  $pagination->total(),
            ]
        ];
    }

    public static function get(int $id)
    {
        return Institution::byKey('id', $id);
    }

    public static function create($request)
    {
        InstitutionHelper::validateRequest($request);

        $nameTenantDatabase = 'academico_' . str_replace("-", "_", $request->subdomain) . '_tenant';

        InstitutionHelper::existsDatabase($nameTenantDatabase);

        $newTenant = Tenant::create([
            'tenancy_db_name' => $nameTenantDatabase
        ]);

        $newDomain = $newTenant->domains()->create([
            'domain' => $request->subdomain
        ]);

        $institution = Institution::create([
            'domain_id' => $newDomain->id,
            'modular_code' => $request->modular_code,
            'ruc' => $request->ruc,
            'name' => $request->name,
            'type_management' => $request->type_management,
            'department' => $request->department,
            'province' => $request->province,
            'district' => $request->district,
            'address' => $request->address,
            'is_active' => true,
            'start_date' => Carbon::now(),
            'end_date' => null,
        ]);

        $limitSpace = SystemConfigurationHelper::getDefaultLimitSpace();
        $folderName = 'academico_' . str_replace("-", "_", $request->subdomain) . '_space';

        $institution->storage()->create([
            'folder_name'   => $folderName,
            'limit_space_mb' => $limitSpace,
            'used_space_mb' => 0,
            'free_space_mb' => $limitSpace
        ]);

        DB::table("$nameTenantDatabase.system_configuration")
            ->where('key', 'application_name')
            ->update(['value' => $request->name]);

        $institution->subdomain = $request->subdomain;

        return $institution;
    }

    public static function update(int $id, $request)
    {
        InstitutionHelper::validateRequest($request, $id);

        $institution = Institution::byKey('id', $id);

        $institution->update([
            'modular_code' => $request->modular_code,
            'ruc' => $request->ruc,
            'name' => $request->name,
            'type_management' => $request->type_management,
            'department' => $request->department,
            'province' => $request->province,
            'district' => $request->district,
            'address' => $request->address
        ]);

        $institution->subdomain = $request->subdomain;

        return $institution;
    }

    public static function disable(int $id)
    {
        $institution = Institution::byKey('id', $id);

        $now = Carbon::now();
        $is_active = !$institution->is_active;

        $institution->update([
            'is_active' => $is_active,
            'start_date' => $is_active ? $now : $institution->start_date,
            'end_date' => $is_active ? null : $now,
        ]);

        return 'Institución ' . ($is_active ? 'activada' : 'desactivada') . ' correctamente';
    }

    public static function subscription(int $id, Request $request)
    {
        InstitutionHelper::validateSubscriptionRequest($request);

        $institution = Institution::byKey('id', $id);

        $now = Carbon::now();
        $start_date = Carbon::parse($request->start_date);

        $is_active = $now >= $start_date;

        $institution->update([
            'is_active' => $is_active,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return 'Suscripción actualizada correctamente';
    }

    public static function existSubdomain(string $subdomain, Request $request)
    {
        $ignorequery = $request->input('ignore');

        $ignore = explode(";",$ignorequery);

        $result = Domain::where('domain', $subdomain)->whereNotIn('domain',$ignore)->exists();

        return $result;
    }

    public static function createUser(Request $request)
    {
        $institutionId = $request->institutionId;

        $parameterDomain = SystemConfigurationHelper::getDomain();
        $parameterUser = SystemConfigurationHelper::getDefaultUser();

        $institution = Institution::where('id', $institutionId)->with('domain.tenant')->first();

        if (!$institution) {
            throw new Exception('Institución no encontrada.');
        }

        $domainTenant = $institution->domain->domain;

        $tenant = $institution->domain->tenant;

        tenancy()->initialize($tenant);

        $person = Person::create([
            'document_type' => 'D.N.I.',
            'document_number' => 'xxxxxxxx',
            'names' => $parameterUser->names,
            'phone' => 'xxxxxxxxx',
            'email' => $parameterUser->email
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'email' => $parameterUser->email,
            'rol_id' => RolTenant::ADMINISTRADOR,
            'password' => Hash::make($parameterUser->password),
            'default_user' => true
        ]);

        $rolIdInstitution = RolTenant::ADMINISTRADOR;

        $user->roles()->attach([$rolIdInstitution]);

        tenancy()->end();

        return [
            'subdomain' => $domainTenant . '.' . $parameterDomain,
            'url' =>  'https://' . $domainTenant . '.' . $parameterDomain,
            'institutionId' => $institutionId,
            'domain' => $parameterDomain,
            'tenatSubdomain' => $domainTenant,
            'user' =>  [
                'email' => $parameterUser->email,
                'name' => $parameterUser->names,
                'password' => $parameterUser->password
            ]
        ];
    }

    public static function detail(int $id)
    {
        $institution = Institution::byKey('id', $id);
        $domain = $institution->domain;
        $tenant = $domain->tenant->tenancy_db_name;
        $storage = $institution->storage;

        $parameterDomain = SystemConfigurationHelper::getDomain();

        $institutionDomain =  $domain->domain . '.' . $parameterDomain;
        $institutionUrl = 'https://' . $institutionDomain;

        $lastPeriod = DB::table("$tenant.period")
            ->orderBy('name', 'desc')
            ->first();

        $totalStudents = $lastPeriod ? DB::table("$tenant.student")
            ->where('period_id', $lastPeriod->id)
            ->count() : 0;

        $totalTeachers = $lastPeriod ? DB::table("$tenant.staff")
            ->where('period_id', $lastPeriod->id)
            ->where('type', 'DOCENTE')
            ->count() : 0;

        $totalCourses = $lastPeriod ? DB::table("$tenant.classroom")
            ->where('period_id', $lastPeriod->id)
            ->count() : 0;

        $institutionDetail = [
            'name' => $institution->name,
            'icon' => $institution->logo ?? 'default/logo.png',
            'url' => $institutionUrl,
            'resumen' => [
                'totalStudents' => $totalStudents,
                'totalTeachers' => $totalTeachers,
                'totalCourses' => $totalCourses
            ]
        ];

        $groups = collect([
            ['name' => 'Documentos', 'icon' => 'mdi-file-document-multiple-outline'],
            ['name' => 'Imágenes', 'icon' => 'mdi-image-multiple-outline'],
            ['name' => 'Videos', 'icon' => 'mdi-movie-play-outline'],
            ['name' => 'Otros', 'icon' => 'mdi-file-multiple-outline']
        ]);

        $files = DB::table("$tenant.file")
            ->selectRaw("
            CASE
                WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.type')) IN (
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                ) THEN 'Documentos'
                WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.type')) LIKE 'image/%' THEN 'Imágenes'
                WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.type')) LIKE 'video/%' THEN 'Videos'
                ELSE 'Otros'
            END as name,
            COUNT(*) as count,
            SUM(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.size'))) as sizeMb
            ")
            ->groupBy('metadata')
            ->get();

        $files = $files->keyBy('name');

        $groupedFiles = [];
        foreach ($groups as $group) {
            $fileType = $group['name'];

            $groupedFiles[] = [
                'name' => $fileType,
                'icon' => $group['icon'],
                'count' => $files->get($fileType)->count ?? 0,
                'sizeMb' => round($files->get($fileType)->sizeMb ?? 0, 2)
            ];
        }

        $storageDetail = [
            'chart' => [
                'total' => floatval($storage->limit_space_mb),
                'used' => floatval($storage->used_space_mb),
                'free' => floatval($storage->free_space_mb),
            ],
            'detail' => $groupedFiles,
        ];

        $userDefault = SystemConfigurationHelper::getDefaultUser();

        Log::error("tenant: $tenant, userDefault: " . json_encode($userDefault));

        $tenantUser = DB::table("$tenant.user")
            ->select('user.email', 'person.names')
            ->join("$tenant.person", 'user.person_id', 'person.id')
            ->where('user.email', $userDefault->email)
            ->first();


        $user = $tenantUser
            ? [
                'name' => $tenantUser->names,
                'email' => $tenantUser->email,
                'password' => $userDefault->password
            ]
            : null;

        $credentialsDetail = [
            'institutionId' => $id,
            'subdomain' => $institutionDomain,
            'domain' => $parameterDomain,
            'url' => $institutionUrl,
            'tenatSubdomain' => $domain->domain,
            'user' => $user
        ];

        $result = [
            'institution' => $institutionDetail,
            'storage' => $storageDetail,
            'credentials' => $credentialsDetail,
        ];

        return $result;
    }

    public static function resizeStorage(Request $request)
    {
        $size = $request->input('size');
        $institutionId = $request->input('institutionId');

        $storage = InstitutionStorage::where('institution_id', $institutionId)->first();

        if (!$storage) {
            throw new Exception("Información de almacenamiento no disponible.");
        }

        $sizeMb = $size * 1024;

        if ($sizeMb < $storage->used_space_mb) {
            throw new Exception("El nuevo limite debe ser mayor al actual usado por la institución.");
        }

        $storage->update([
            'limit_space_mb' => $sizeMb
        ]);

        return $sizeMb;
    }

    public static function updateSubdomain(Request $request)
    {

        $institutionId = $request->input('institutionId');

        $subdomain = $request->input('subdomain');
        
        $exists = Domain::where('domain', $subdomain)->whereHas('institution',function($subquery) use ($institutionId) {
            return $subquery->whereNot('id',$institutionId);
        })->exists();

        if ($exists) {
            throw new Exception("El subdominio ingresado ya existe.");
        }

        Domain::whereHas('institution', function ($query) use ($institutionId) {
            $query->where('id', $institutionId);
        })->update(['domain' => $subdomain]);
        
        $parameterDomain = SystemConfigurationHelper::getDomain();

        $url =  'https://' . $subdomain . '.' . $parameterDomain;
        
        return [
            'domain' => $parameterDomain,
            'subdomain' => $subdomain,
            'url' => $url
        ];

    }


    public static function delete(int $id, Request $request) {

        $institution = Institution::where('id',$id)->with('storage','domain')->first();

        if (!$institution) {
            throw new Exception("Institución no encontrada.");
        }

        $domain = $institution->domain;

        $storage = $institution->storage;

        $tenant = $domain->tenant()->first();

        FileSystemService::delete($storage->folder_name);

        $tenant->forceDelete();

        $domain->forceDelete();

        $storage->forceDelete();

        $institution->forceDelete();

        return "Institución eliminada correctamente.";
    }
}

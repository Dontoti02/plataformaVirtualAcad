<?php

namespace Modules\Tenant\Modules\SystemConfiguration\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Services\CentralConfig;
use Modules\Admin\Models\Institution;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\SystemConfiguration\Models\SystemConfiguration;
use Modules\Tenant\Modules\User\Helpers\RolHelper;

class SystemConfigurationRepository
{
    public static function general()
    {
        $application_name = AdminSystemConfigurationHelper::getName();
        $institution_name = SystemConfiguration::byKey('key', 'application_name');
        $logo = SystemConfiguration::byKey('key', 'logo');
        $favicon = SystemConfiguration::byKey('key', 'favicon');
        $banner = SystemConfiguration::byKey('key', 'banner');
        $primary_color = SystemConfiguration::byKey('key', 'primary_color');
        $redirectLinks = SystemConfiguration::byKey('key', 'redirect_links');
        $dates = [
            $institution_name->updated_at,
            $logo->updated_at,
            $favicon->updated_at,
            $banner->updated_at,
            $primary_color->updated_at,
        ];
        $last_date = max($dates)->format('Y-m-d H:i:s');

        $result = [
            'app_name' => $application_name,
            'institution_name' => $institution_name->value,
            'logo' => $logo->value,
            'favicon' => $favicon->value,
            'banner' => $banner->value,
            'primary_color' => $primary_color->value,
            'last_date' => $last_date,
            'redirect_links' => $redirectLinks->value ? json_decode($redirectLinks->value, true) : [],
        ];

        return $result;
    }

    public static function landingPage()
    {
        $system_configuration = SystemConfigurationHelper::getLandingPage();

        $logo = SystemConfigurationHelper::getInstitutionLogo();

        if ($logo) {
            $system_configuration->institution->logo = $logo;
        }

        // La URL del frontend de esta institución sale de su configuración local:
        // antes se componía con el subdominio y el dominio central.
        $landing = CentralConfig::frontendUrl().'/landing';

        $system_configuration->url = $landing;

        return $system_configuration;
    }

    public static function list()
    {
        $system_configuration = SystemConfiguration::select([
            'key',
            'name',
            'type',
            'value',
        ])
            ->where('key', '!=', 'landing_page')
            ->get()
            ->each(function ($item) {
                SystemConfigurationHelper::setTypeValue($item);
            });

        return $system_configuration;
    }

    public static function update(string $key, Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        SystemConfigurationHelper::validateUpdateRequest($key, $request);

        $system_configuration = SystemConfiguration::byKey('key', $key);

        if (in_array($key, ['logo', 'favicon', 'banner'])) {

            if ($system_configuration->value) {
                $system_configuration->value = 'public/' . $system_configuration->value;

                if (Storage::exists($system_configuration->value)) {
                    Storage::delete($system_configuration->value);
                }
            }

            if (!$request->value || $request->value === 'null') {
                $request->merge(['path' => $system_configuration->value]);
                self::deleteImage($request);
                $system_configuration->update(['value' => null]);
                return "$system_configuration->name eliminado correctamente";
            }

            $file = $request->file('value');
            $path = $file->store('public/' . $key);
            $path = str_replace('public/', '', $path);

            $system_configuration->update(['value' => $path]);

            if ($key == 'logo') {
                CentralConfig::updateInstitution(['logo' => $path]);
            }

            return $system_configuration->value;
        }

        if ($key == 'study_hour_start') {
            $aux = SystemConfiguration::byKey('key', 'study_hour_end');

            $hour_start = Carbon::createFromFormat('H:i', $request->value);
            $hour_end = Carbon::createFromFormat('H:i', $aux->value);
            if ($hour_start >= $hour_end) {
                throw new Exception('La hora de inicio de estudio debe ser menor a la hora de fin de estudio');
            }
        }

        if ($key == 'study_hour_end') {
            $aux = SystemConfiguration::byKey('key', 'study_hour_start');

            $hour_end = Carbon::createFromFormat('H:i', $request->value);
            $hour_start = Carbon::createFromFormat('H:i', $aux->value);
            if ($hour_end <= $hour_start) {
                throw new Exception('La hora de fin de estudio debe ser mayor a la hora de inicio de estudio');
            }
        }

        $system_configuration->update(['value' => $request->value === 'null' ? null : $request->value]);

        if ($key == 'application_name') {
            CentralConfig::updateInstitution(['name' => $request->value]);
        }

        if ($key == 'landing_page') {
            return self::landingPage();
        }

        return "$system_configuration->name actualizado correctamente";
    }

    public static function uploadImage(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        SystemConfigurationHelper::validateUploadImageRequest($request);

        $file = $request->file('file');
        $path = $file->store('public/images');
        $path = str_replace('public/', '', $path);

        return $path;
    }

    public static function deleteImage(Request $request)
    {
        RolHelper::validateSecretaryOrAdminAccess();

        if (!str_starts_with($request->path, 'default/')) {

            $path = 'public/' . $request->path;

            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }

        return "Imagen eliminada correctamente";
    }
}

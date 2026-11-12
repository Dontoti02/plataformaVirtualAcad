<?php

namespace Modules\Admin\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Helpers\InstitutionHelper;
use Modules\Admin\Helpers\SystemConfigurationHelper;
use Modules\Admin\Models\SystemConfiguration;

class SystemConfigurationRepository
{
    public static function general()
    {
        $application_name = SystemConfiguration::byKey('key', 'application_name');
        $logo = SystemConfiguration::byKey('key', 'logo');
        $favicon = SystemConfiguration::byKey('key', 'favicon');
        $banner = SystemConfiguration::byKey('key', 'banner');

        $dates = [
            $application_name->updated_at,
            $logo->updated_at,
            $favicon->updated_at,
            $banner->updated_at,
        ];
        $last_date = max($dates)->format('Y-m-d H:i:s');

        $result = [
            'app_name' => $application_name->value,
            'logo' => $logo->value,
            'favicon' => $favicon->value,
            'banner' => $banner->value,
            'last_date' => $last_date,
        ];

        return $result;
    }

    public static function list()
    {
        $system_configuration = SystemConfiguration::select([
            'key',
            'name',
            'type',
            'value',
        ])
            ->where('key', '!=', 'domain')
            ->get()
            ->each(function ($item) {
                SystemConfigurationHelper::setTypeValue($item);
            });

        return $system_configuration;
    }

    public static function update(string $key, Request $request)
    {
        SystemConfigurationHelper::validateUpdateRequest($key, $request);

        $system_configuration = SystemConfiguration::byKey('key', $key);
 
        if ($key == 'domain') {
            throw new Exception('No se puede modificar el dominio');
        }

        if ($key === 'user_default_auth_institution') {

            $value = json_decode($request->value);

            InstitutionHelper::updateDefaultAdminUser(
                $value->email,
                $value->password,
                $value->names
            );
        }

        if (in_array($key, ['logo', 'favicon', 'banner'])) {

            if ($system_configuration->value) {
                $system_configuration->value = 'public/' . $system_configuration->value;

                if (Storage::exists($system_configuration->value)) {
                    Storage::delete($system_configuration->value);
                }
            }

            if (!$request->value) {
                return "$system_configuration->name eliminado correctamente";
            }

            $file = $request->file('value');
            $path = $file->store('public/' . $key);
            $path = str_replace('public/', '', $path);

            $system_configuration->update(['value' => $path]);

            return $system_configuration->value;
        }

        $system_configuration->update(['value' => $request->value]);

        return "$system_configuration->name actualizado correctamente";
    }
}

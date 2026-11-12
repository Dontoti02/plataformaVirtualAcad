<?php

namespace Modules\Tenant\Modules\User\Repositories;

use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\User\Models\User;

class ProfileRepository
{
    public static function get()
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        $roles = $user->roles()
            ->select('rol.id', 'rol.name')
            ->get();

        $period = PeriodHelper::current();

        $size_mb = SystemConfigurationHelper::getValueByKey('maximum_file_size_to_upload');

        $mimes = SystemConfigurationHelper::getValueByKey('extensions_allowed_to_upload');
        $mimes = array_filter($mimes, function ($item) {
            return $item->permitted == true;
        });
        $mimes = array_values($mimes);
        $mimes = array_map(function ($item) {
            return $item->extension;
        }, $mimes);

        $result = [
            'user'  => [
                'document_type' => $user->person->document_type,
                'document_number' => $user->person->document_number,
                'names' => $user->person->names,
                'phone' => $user->person->phone,
                'email' => $user->email,
                'photo' => $user->avatar,
            ],
            'current_role' => $user->rol_id,
            'roles' => $roles,
            'period' => $period,
            'maximum_file_size_to_upload' => $size_mb,
            'extensions_allowed_to_upload' => $mimes,
        ];

        return $result;
    }
}

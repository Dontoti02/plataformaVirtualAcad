<?php

namespace Modules\Tenant\Modules\Home\UseCases;

use Exception;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Home\Repositories\HomeSecretaryRepository;
use Modules\Tenant\Modules\Home\Repositories\HomeStudentRepository;
use Modules\Tenant\Modules\Home\Repositories\HomeTeacherRepository;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\User;

class HomeUseCases
{
    public static function home()
    {
        try {

            $session = SessionManager::get();
            $user = User::find($session->id);

            switch ($user->rol_id) {
                case RolTenant::STUDENT:
                    $result = HomeStudentRepository::home();
                    break;
                case RolTenant::TEACHER:
                    $result = HomeTeacherRepository::home();
                    break;
                default:
                    $result = HomeSecretaryRepository::home();
                    break;
            }

            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

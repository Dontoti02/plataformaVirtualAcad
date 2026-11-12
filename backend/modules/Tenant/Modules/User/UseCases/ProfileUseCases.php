<?php

namespace Modules\Tenant\Modules\User\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\User\Repositories\ProfileRepository;

class ProfileUseCases
{
    public static function get()
    {
        try {
            $result = ProfileRepository::get();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

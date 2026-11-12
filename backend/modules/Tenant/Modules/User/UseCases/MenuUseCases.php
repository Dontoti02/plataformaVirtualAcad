<?php

namespace Modules\Tenant\Modules\User\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\User\Repositories\MenuRepository;

class MenuUseCases
{
    public static function get()
    {
        try {
            $result = MenuRepository::get();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

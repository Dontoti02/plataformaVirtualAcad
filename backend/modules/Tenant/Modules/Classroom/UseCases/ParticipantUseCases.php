<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\ParticipantRepository;

class ParticipantUseCases
{
    public static function list(int $classroom_id)
    {
        try {
            $result = ParticipantRepository::list($classroom_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

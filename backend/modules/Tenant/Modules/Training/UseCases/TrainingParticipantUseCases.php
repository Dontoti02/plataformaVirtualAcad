<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingParticipantRepository;

class TrainingParticipantUseCases
{
    public static function list(int $training_id)
    {
        try {
            $result = TrainingParticipantRepository::list($training_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

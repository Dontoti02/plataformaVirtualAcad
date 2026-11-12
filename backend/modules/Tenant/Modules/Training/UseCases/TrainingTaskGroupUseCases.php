<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingTaskGroupRepository;

class TrainingTaskGroupUseCases
{
    public static function list(int $training_content_id)
    {
        try {
            $result = TrainingTaskGroupRepository::list($training_content_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function set(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingTaskGroupRepository::set($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $result = TrainingTaskGroupRepository::delete($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function listParticipants(int $training_id)
    {
        try {
            $result = TrainingTaskGroupRepository::listParticipants($training_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function setParticipant(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingTaskGroupRepository::setParticipant($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function deleteParticipant(int $id)
    {
        DB::beginTransaction();
        try {
            $result = TrainingTaskGroupRepository::deleteParticipant($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}

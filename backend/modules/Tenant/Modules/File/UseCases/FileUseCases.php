<?php

namespace Modules\Tenant\Modules\File\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\File\Repositories\FileRepository;

class FileUseCases
{
    public static function download(string $uuid)
    {
        try {
            [$binary, $type, $filename] = FileRepository::download($uuid);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function upload(Request $request)
    {
        try {
            $result = FileRepository::upload($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id, string $model)
    {
        try {
            $result = FileRepository::delete($id, $model);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

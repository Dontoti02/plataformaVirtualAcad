<?php

namespace Modules\Tenant\Modules\Export\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Export\Repositories\ExportRepository;

class ExportUseCases
{
    public static function execute(string $key, string $type, Request $request)
    {
        try {
            [$binary, $type, $filename] = ExportRepository::execute($key, $type, $request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}

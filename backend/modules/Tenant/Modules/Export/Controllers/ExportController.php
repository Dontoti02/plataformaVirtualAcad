<?php

namespace Modules\Tenant\Modules\Export\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Export\UseCases\ExportUseCases;

class ExportController extends Controller
{
    public function execute(string $key, string $type, Request $request)
    {
        return ExportUseCases::execute($key,  $type, $request);
    }
}

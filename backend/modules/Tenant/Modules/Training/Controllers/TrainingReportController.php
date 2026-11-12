<?php

namespace Modules\Tenant\Modules\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Training\UseCases\TrainingReportUseCases;

class TrainingReportController extends Controller
{
    public function filters()
    {
        return TrainingReportUseCases::filters();
    }

    public function list(Request $request)
    {
        return TrainingReportUseCases::list($request);
    }

    public function listDownload(Request $request)
    {
        return TrainingReportUseCases::listDownload($request);
    }
}

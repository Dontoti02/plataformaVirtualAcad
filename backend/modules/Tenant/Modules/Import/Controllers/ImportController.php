<?php

namespace Modules\Tenant\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Import\UseCases\ImportUseCases;

class ImportController extends Controller
{
    public function list()
    {
        return ImportUseCases::list();
    }

    public function get(int $id)
    {
        return ImportUseCases::get($id);
    }

    public function finish(Request $request)
    {
        return ImportUseCases::finish($request);
    }

    public function finishAll(Request $request)
    {
        return ImportUseCases::finishAll();
    }

    public function currently()
    {
        return ImportUseCases::currently();
    }

    public function history(int $id)
    {
        return ImportUseCases::history($id);
    }

    public function execute(string $key, Request $request)
    {
        return ImportUseCases::execute($key, $request);
    }
}

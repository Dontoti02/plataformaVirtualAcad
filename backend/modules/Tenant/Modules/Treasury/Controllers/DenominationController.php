<?php

namespace Modules\Tenant\Modules\Treasury\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Treasury\Models\Denomination;

class DenominationController extends Controller
{
  public function all(Request $request) {
    return  Response::success(Denomination::all(['id','name'])); 
  }
}
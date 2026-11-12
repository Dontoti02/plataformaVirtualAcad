<?php

namespace Modules\Tenant\Modules\Treasury\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Treasury\UseCases\PaymentsUseCases;

class PaymentsController extends Controller
{
    public function get(int $person_id,int $is_paid)
    {
      return PaymentsUseCases::get($person_id,$is_paid);
    }
    public function searchStudent(Request $request)
    {
      return PaymentsUseCases::searchStudent($request);
    }
    public function create(Request $request)
    {
      return PaymentsUseCases::create($request);
    }
    public function detail(int $id)
    {
      return PaymentsUseCases::detail($id);
    }
    public function payNextDetail(int $id){
      return PaymentsUseCases::payNextDetail($id);
    }

    public function exportTicket(int $id) {
      set_time_limit(500);
      return PaymentsUseCases::exportTicket($id);
    }
    
    public function exportMovementTicket(int $id) {
      set_time_limit(500);
      return PaymentsUseCases::exportMovementTicket($id);
    }

    public function movementsByConcept(int $concept_id)
    {
      return PaymentsUseCases::movementsByConcept($concept_id);
    }
    
    public function refund(Request $request)
    {
      return PaymentsUseCases::refund($request);
    }
    
    public function payEnrollment(int $enrollId,Request $request)
    {
      return PaymentsUseCases::payEnrollment($enrollId,$request);
    }
}
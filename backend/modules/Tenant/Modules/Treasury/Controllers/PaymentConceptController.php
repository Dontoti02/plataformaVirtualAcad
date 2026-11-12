<?php

namespace Modules\Tenant\Modules\Treasury\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Modules\Treasury\UseCases\PaymentConceptUseCases;

class PaymentConceptController extends Controller
{
    public function all(Request $request)
    {
      return PaymentConceptUseCases::all($request);
    }

    public function create(Request $request)
    {
      return PaymentConceptUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
      return PaymentConceptUseCases::update($id, $request);
    }

    public function active(int $id)
    {
      return PaymentConceptUseCases::active($id);
    }

    public function inactive(int $id)
    {
      return PaymentConceptUseCases::inactive($id);
    }

    public function delete(int $id)
    {
      return PaymentConceptUseCases::delete($id);
    }
    public function search(Request $request)
    {
      return PaymentConceptUseCases::search($request);
    }
    public function enrollmentConceptsData()
    {
      return PaymentConceptUseCases::enrollmentConceptsData();
    }
    public function history(int $id)
    {
      return PaymentConceptUseCases::history($id);
    }
    public function movements(int $id)
    {
      return PaymentConceptUseCases::movements($id);
    }
}
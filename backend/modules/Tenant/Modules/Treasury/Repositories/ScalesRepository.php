<?php

namespace Modules\Tenant\Modules\Treasury\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Enroll\Models\Scale;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\Treasury\Adapters\PaymentConceptAdapter;
use Modules\Tenant\Modules\Treasury\Adapters\ScalesAdapter;
use Modules\Tenant\Modules\Treasury\FormRequest\PaymentConceptForm;
use Modules\Tenant\Modules\Treasury\Helpers\MovementHelper;
use Modules\Tenant\Modules\Treasury\Helpers\PaymentConceptHelper;
use Modules\Tenant\Modules\Treasury\Helpers\ScalesHelper;
use Modules\Tenant\Modules\User\Models\Person;
use Modules\Tenant\Modules\User\Models\User;

class ScalesRepository
{

  public static function create(Request $request)
  {

    $name = $request->name;
    $scale_amount= $request->scale_amount;
    $scale= Scale::create([
      'name' => $name,
      'scale_amount' => $scale_amount,
    ]);

    return $scale;
  }
  public static function all(Request $request)
  {

    $search = $request->input('search');
    $page   = (int) $request->input('page', 1);
    $limit  = (int) $request->input('limit', 10);

    $items = collect();

    $paginated = ScalesHelper::getPaginated($page, $limit, $search);

    foreach ($paginated->items() as $item) {
      $items->push(ScalesAdapter::transform($item));
    }

    return  [
      'items' => $items,
      'pagination' => [
        'page' => $paginated->currentPage(),
        'pages' => $paginated->lastPage(),
        'total' =>  $paginated->total(),
      ]
    ];
  }

  public static function update(Request $request, int $id)
  {
    $scale = Scale::find($id);
    $scale->name = $request->name;
    $scale->scale_amount = $request->scale_amount;
    $scale->save();
    return $scale;
  }

  public static function delete( int $id)
  {
    $scale = Scale::find($id);
    $scale->delete();
    return $scale;
  }

  public static function enrollments(int $id)
  {
    $scale = Scale::find($id);
    $enrolls= $scale->enrolls;
    $persons_names=$enrolls->pluck('person_id')->map(function($person_id){
      return Person::find($person_id)->names;
    });
    return $persons_names;
  }


}

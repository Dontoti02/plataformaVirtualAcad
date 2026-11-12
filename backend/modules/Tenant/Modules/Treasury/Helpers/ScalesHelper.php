<?php

namespace Modules\Tenant\Modules\Treasury\Helpers;

use Modules\Tenant\Modules\Enroll\Models\Scale;

class ScalesHelper {

  public static function getPaginated(int $page,int $limit,$search = null, string $sort = '') {
   
    $query = Scale::query();

    if ($search) {
      $query->where(function($q) use ($search) {
        $q->where('name', 'like', '%' . $search . '%');
      });
    }

    $query->orderBy('id', 'asc');
    return $query->paginate($limit, ['*'], 'page', $page);
  }

  public static function find($id) {
    return Scale::find($id);
  }

  public static function exists($id) {
    return Scale::where('id', $id)->exists();
  }

  public static function hasEqualName($name, $id = null) {
    $query = Scale::where('name', $name);
    if ($id) {
      $query->where('id', '!=', $id);
    }
    return $query->exists();
  }

  public static function create(array $data) {
    $item = new Scale([
        'name' => $data['name'],
        'scale_amount' => $data['scale_amount'],
    ]);
    $item->save();
    return $item->fresh();
  }
  


}

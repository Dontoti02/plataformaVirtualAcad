<?php

namespace Modules\Tenant\Modules\User\Helpers;

use Modules\Shared\Services\CentralConfig;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\SystemConfiguration\Models\SystemConfiguration;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Modules\Tenant\Modules\User\Models\Menu;
use Modules\Tenant\Modules\User\Models\Option;

class MenuHelper
{
  public static function listByUser($user, $period)
  {

    $is_admin = in_array($user->rol_id, [
      RolTenant::ADMINISTRADOR,
      RolTenant::DIRECTOR_GENERAL,
      RolTenant::JEFE_UNIDAD_ACADEMICA,
      RolTenant::COORDINADOR_ACADEMICO,
    ]);
    $excluded_options = !$period && $is_admin ? ['UsersList', 'AcademicPeriods', 'ListOfMerit'] : [];
    // Menú de cada módulo contratable: se oculta si el módulo no está vigente,
    // la misma regla que aplican sus rutas. Antes salía de la BD central, que en
    // un VPS por institución no existe; ahora, de la tabla local.
    $menuInstitutionTableKey = [
      'bolsa_laboral' => 'Bolsa Laboral',
    ];

    $excluded_menu = [];

    foreach ($menuInstitutionTableKey as $moduleKey => $menuName) {
      if (!CentralConfig::moduleEnabled($moduleKey)) {
        $excluded_menu[] = Menu::where('name', $menuName)->value('id');
      }
    }

    $excluded_menu = array_filter($excluded_menu);

    $menus = Menu::select('menu.id', 'menu.name')
      ->join('option', 'menu.id', 'option.menu_id')
      ->join('rol_option', 'option.id', 'rol_option.option_id')
      ->where('rol_option.rol_id', $user->rol_id)
      ->whereNotIn('menu.id', $excluded_menu)
      ->groupBy('menu.id', 'menu.name')
      ->get();

    $result = [];
    foreach ($menus as $menu) {
      $options = self::getOptions($user->rol_id, $menu->id, null, $excluded_options);
      if (count($options) == 0) continue;
      $menu->options = $options;
      $result[] = $menu;
    }

    $links = SystemConfiguration::select([
      'key',
      'name',
      'value',
    ])
      ->whereIn('key', ['virtual_library_url', 'institutional_repository_url','external_job_opportunities'])
      ->whereNotNull('value')
      ->get();

    foreach ($links as $link) {
      SystemConfigurationHelper::setTypeValue($link);
      $result[] = [
        'id' => $link->key,
        'name' => $link->key,
        'url' => $link->value,
      ];
    }

    return $result;
  }

  public static function getOptions($rol_id, $menu_id, $option_id, $excluded_options)
  {
    $options = Option::select([
      'option.id',
      'option.name',
      'option.name_url',
      'option.icon',
    ])
      ->join('rol_option', 'option.id', 'rol_option.option_id')
      ->where('option.menu_id', $menu_id)
      ->where('rol_option.rol_id', $rol_id)
      ->where('option.is_visible', true)
      ->where('option.option_id', $option_id)
      ->whereNotIn('option.name_url', $excluded_options)
      ->get();

    foreach ($options as $option) {
      $option->options = self::getOptions($rol_id, $menu_id, $option->id, $excluded_options);
    }

    return $options;
  }
}

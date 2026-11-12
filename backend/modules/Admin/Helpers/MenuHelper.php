<?php

namespace Modules\Admin\Helpers;

use Modules\Admin\Models\Menu;
use Modules\Admin\Models\Option;

class MenuHelper
{
    public static function listByUser($user)
    {
        $menu = Menu::select('menu.id', 'menu.name')
            ->join('option', 'menu.id', 'option.menu_id')
            ->join('rol_option', 'option.id', 'rol_option.option_id')
            ->where('rol_option.rol_id', $user->rol_id)
            ->groupBy('menu.id', 'menu.name')
            ->get()
            ->each(function ($menu) use ($user) {
                $menu->options = self::getOptions($user->rol_id, $menu->id);
            });

        return $menu;
    }

    public static function getOptions($rol_id, $menu_id, $option_id = null)
    {
        $options = Option::select([
            'option.id',
            'option.name',
            'option.name_url',
            'option.icon',
            'option.is_visible',
        ])
            ->join('rol_option', 'option.id', 'rol_option.option_id')
            ->where('option.menu_id', $menu_id)
            ->where('rol_option.rol_id', $rol_id)
            ->where('option.option_id', $option_id)
            ->orderBy('option.order', 'asc')
            ->get()
            ->each(function ($option) use ($rol_id, $menu_id) {
                $option->options = self::getOptions($rol_id, $menu_id, $option->id);
            });

        return $options;
    }
}

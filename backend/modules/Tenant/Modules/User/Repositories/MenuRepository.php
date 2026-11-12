<?php

namespace Modules\Tenant\Modules\User\Repositories;

use Modules\Shared\Helpers\SessionManager;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\User\Helpers\MenuHelper;
use Modules\Tenant\Modules\User\Models\User;

class MenuRepository
{
    public static function get()
    {
        $session = SessionManager::get();
        $user = User::find($session->id);

        $period = PeriodHelper::current();
        $menu = MenuHelper::listByUser($user, $period);

        return $menu;
    }
}

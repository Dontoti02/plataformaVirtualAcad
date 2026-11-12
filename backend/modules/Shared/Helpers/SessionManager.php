<?php

namespace Modules\Shared\Helpers;

use Illuminate\Support\Facades\Request;

class SessionManager
{
    
    const JWT_SESSION = 'jwt_session_payload';
     
    /**
     * Get session
     *
     * @return collect|null
     */
    public static function get()
    {

        return (object)Request::get(self::JWT_SESSION);
    }

}

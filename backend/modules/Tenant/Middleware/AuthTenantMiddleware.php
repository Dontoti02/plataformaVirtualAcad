<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Helpers\SessionManager;
use Modules\Shared\Services\JWTService;
use Modules\Shared\Utils\Response;

class AuthTenantMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $this->getToken($request);

            if (!$token) {
                return Response::unauthorized();
            }

            $payload = JWTService::valid($token);

            $request->merge([SessionManager::JWT_SESSION => $payload]);

            return $next($request);
        } catch (Exception $e) {
            return Response::unauthorized();
        }
    }


    private function getToken($request)
    {
        if (empty($token)) $token = $request->header('Authorization');

        if (empty($token)) $token = $request->bearerToken();

        $token = str_replace('Bearer ', '', $token);

        $token = str_replace(' ', '', $token);

        return $token;
    }
}

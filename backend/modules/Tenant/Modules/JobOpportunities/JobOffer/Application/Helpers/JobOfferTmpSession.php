<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Helpers;

use Illuminate\Http\Request;
use Modules\Shared\Services\JWTService;

class JobOfferTmpSession
{
  public static function get(Request $request)
  {
    return self::handle($request);
  }

  public static function handle(Request $request)
  {
    $token = self::getToken($request);
    if (!$token) {
      return null;
    }
    $payload = JWTService::valid($token);
    return (Object)$payload;
  }


  private static function getToken($request)
  {
    if (empty($token)) $token = $request->header('Authorization');
    if (empty($token)) $token = $request->bearerToken();
    $token = str_replace('Bearer ', '', $token);
    $token = str_replace(' ', '', $token);
    return $token;
  }
}

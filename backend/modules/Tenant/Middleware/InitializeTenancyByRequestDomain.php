<?php

declare(strict_types=1);

namespace Modules\Tenant\Middleware;

use Closure;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Modules\Admin\Models\Domain;
use Stancl\Tenancy\Exceptions\NotASubdomainException;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class InitializeTenancyByRequestDomain extends InitializeTenancyByRequestData
{

    public static $subdomainIndex = 0;

     /** @var callable|null */
     public static $onFail;

    /** @var string|null */
    public static $header = 'X-subdomain';
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $subdomain = $this->makeSubdomain($request->header('X-subdomain')); 

        if (is_object($subdomain) && $subdomain instanceof Exception) {
            $onFail = static::$onFail ?? function ($e) {
                throw $e;
            };

            return $onFail($subdomain, $request, $next);
        }

        if (is_object($subdomain) && $subdomain instanceof Response) {
            return $subdomain;
        }

        $domainTable = Domain::where('domain',$subdomain)->first();

        if (!$domainTable) {
            $onFail = static::$onFail ?? function ($e) {
                throw $e;
            };
            return $onFail($subdomain, $request, $next);
        }
        
        return $this->initializeTenancy(
            $request,
            $next,
            $domainTable->tenant_id
        );
    }

      /** @return string|Response|Exception|mixed */
    protected function makeSubdomain(string $hostname)
    {
        $parts = explode('.', $hostname);

        $isLocalhost = count($parts) === 1;
        $isIpAddress = count(array_filter($parts, 'is_numeric')) === count($parts);
        $isACentralDomain = in_array($hostname, config('tenancy.central_domains'), true);
        $notADomain = $isLocalhost || $isIpAddress;
        $thirdPartyDomain = ! Str::endsWith($hostname, config('tenancy.central_domains'));

        if ($isACentralDomain || $notADomain || $thirdPartyDomain) {
            return new NotASubdomainException($hostname);
        }

        return $parts[static::$subdomainIndex];
    }
}

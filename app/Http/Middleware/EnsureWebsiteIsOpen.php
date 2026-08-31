<?php

namespace App\Http\Middleware;

use App\Services\AppStatusService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebsiteIsOpen
{
    public function __construct(protected AppStatusService $appStatus)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('website.landing', 'website.closed')) {
            return $next($request);
        }

        try {
            if ($this->appStatus->isWebsiteOpen()) {
                return $next($request);
            }
        } catch (\Throwable) {
            return $next($request);
        }

        return response()->view('website.pages.closewebsite', [], 503);
    }
}

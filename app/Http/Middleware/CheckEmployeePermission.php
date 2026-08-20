<?php

namespace App\Http\Middleware;

use App\Http\Traits\Responser;
use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;

class CheckEmployeePermission
{
    use Responser;

    /**
     * Require the authenticated employee's role to have every given "section.action" permission.
     */
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $employee = $request->user();

        if (! $employee instanceof Employee) {
            return $this->errorMessage(trans('api.unauthorized'), 403);
        }

        foreach ($permissions as $permission) {
            if (! $employee->hasPermission($permission)) {
                return $this->errorMessage(trans('api.unauthorized'), 403);
            }
        }

        return $next($request);
    }
}

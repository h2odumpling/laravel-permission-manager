<?php

namespace H2o\PermissionManager\Middlewares;

use Closure;
use H2o\PermissionManager\Exceptions\PermissionDefinedException;
use H2o\PermissionManager\Exceptions\UnauthorizedException;

class PermissionMiddleware
{
    public function handle($request, Closure $next)
    {
        if(!in_array(env('APP_ENV'), config('h2o-permission.active_env'))){
            return $next($request);
        }

        if (app('auth')->guest()) {
            throw new UnauthorizedException();
        }

        $permission = str_replace('/', '.', $request->path());

        if (app('auth')->user()->can($permission)) {
            return $next($request);
        }

        throw new PermissionDefinedException();
    }
}

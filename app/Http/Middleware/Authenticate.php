<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function unauthenticated($request, array $guards)
    {
        throw new \Illuminate\Auth\AuthenticationException('Unauthenticated Request!');
    }

    protected function redirectTo($request): ?string
    {
        return null; // 🚫 disable redirect
    }
}

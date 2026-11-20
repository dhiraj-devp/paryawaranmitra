<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function portalRoute(string $name, mixed $parameters = []): string
    {
        $portal = request()->user()->portal->value;

        return route($portal.'.'.$name, $parameters);
    }
}

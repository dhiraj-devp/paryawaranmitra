<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('paryavaran.locales', []));
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        $user = $request->user('admin') ?? $request->user('staff');
        $candidate = is_string($sessionLocale) ? $sessionLocale : $user?->locale;

        $locale = is_string($candidate) && in_array($candidate, $supported, true)
            ? $candidate
            : config('app.locale');

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $next($request);
    }
}

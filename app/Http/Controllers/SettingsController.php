<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasPermission('settings.view'), 403);

        return view('settings.index', [
            'timezone' => config('app.timezone'),
            'environment' => config('app.env'),
            'locales' => config('paryavaran.locales'),
            'queue' => config('queue.default'),
            'cache' => config('cache.default'),
            'session' => config('session.driver'),
            'twoFactor' => filter_var(config('security.admin_two_factor'), FILTER_VALIDATE_BOOL),
            'documentsDisk' => config('security.documents_disk'),
        ]);
    }
}

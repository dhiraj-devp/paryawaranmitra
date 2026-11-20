<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RecordLogout;
use App\Enums\Portal;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request, RecordLogout $logout): RedirectResponse
    {
        $portal = Portal::from(Auth::getDefaultDriver());
        $logout->execute($portal, $request);

        return redirect()->route($portal->value.'.login');
    }
}

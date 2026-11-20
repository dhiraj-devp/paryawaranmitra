<?php

namespace App\Http\Controllers;

use App\Enums\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PortalEntryController extends Controller
{
    public function admin(): RedirectResponse
    {
        return $this->redirect(Portal::Admin);
    }

    public function staff(): RedirectResponse
    {
        return $this->redirect(Portal::Staff);
    }

    private function redirect(Portal $portal): RedirectResponse
    {
        return Auth::guard($portal->value)->check()
            ? redirect()->route($portal->value.'.dashboard')
            : redirect()->route($portal->value.'.login');
    }
}

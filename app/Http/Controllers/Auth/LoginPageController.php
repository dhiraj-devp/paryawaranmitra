<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Portal;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LoginPageController extends Controller
{
    public function admin(): View
    {
        return view('auth.login', ['portal' => Portal::Admin]);
    }

    public function staff(): View
    {
        return view('auth.login', ['portal' => Portal::Staff]);
    }
}

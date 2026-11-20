<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CustomerPortalController extends Controller
{
    public function show(): View
    {
        return view('customer.reserved');
    }
}

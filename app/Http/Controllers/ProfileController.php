<?php

namespace App\Http\Controllers;

use App\Actions\Users\UpdateProfile;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'account' => request()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $action): RedirectResponse
    {
        $action->execute($request->user(), $request->profileAttributes());

        return redirect()
            ->to($this->portalRoute('profile.edit'))
            ->with('status', __('ui.profile.saved'));
    }
}

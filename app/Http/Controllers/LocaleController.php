<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request, AuditLogger $audit): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        $request->session()->put('locale', $locale);

        $user = $request->user('admin') ?? $request->user('staff');

        if ($user instanceof User && $user->locale !== $locale) {
            $previous = $user->locale;
            $user->update(['locale' => $locale]);

            $audit->log(
                action: 'profile.locale_changed',
                subject: $user,
                oldValues: ['locale' => $previous],
                newValues: ['locale' => $locale],
                actor: $user,
                portal: $user->portal,
                request: $request,
            );
        }

        return redirect()->to($this->safePrevious($request));
    }

    private function safePrevious(UpdateLocaleRequest $request): string
    {
        $previous = url()->previous();
        $target = parse_url($previous, PHP_URL_HOST);
        $application = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($previous) || $previous === '' || $previous === $request->fullUrl()) {
            return $this->fallback($request);
        }

        if (is_string($target) && is_string($application) && $target !== $application) {
            return $this->fallback($request);
        }

        return $previous;
    }

    private function fallback(UpdateLocaleRequest $request): string
    {
        if ($request->user('admin')) {
            return route('admin.dashboard');
        }

        if ($request->user('staff')) {
            return route('staff.dashboard');
        }

        return route('home');
    }
}

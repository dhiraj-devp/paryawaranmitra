<?php

namespace App\Http\Controllers;

use App\Actions\Users\SaveUser;
use App\Http\Requests\SaveUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = $this->term($request);

        $users = User::query()
            ->with('roles')
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search ?? '',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'account' => tap(new User(['is_active' => true, 'portal' => \App\Enums\Portal::Staff]), function (User $user) {
                $user->setRelation('roles', collect());
            }),
            'roles' => Role::query()->orderBy('portal')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveUserRequest $request, SaveUser $action): RedirectResponse
    {
        $action->execute(new User, $request->userAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('users.index'))
            ->with('status', __('ui.users.saved'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'account' => $user->load('roles'),
            'roles' => Role::query()->orderBy('portal')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveUserRequest $request, User $user, SaveUser $action): RedirectResponse
    {
        $action->execute($user, $request->userAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('users.index'))
            ->with('status', __('ui.users.saved'));
    }

    private function term(Request $request): ?string
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return null;
        }

        return mb_substr($term, 0, 100);
    }
}

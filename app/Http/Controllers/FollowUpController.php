<?php

namespace App\Http\Controllers;

use App\Actions\FollowUps\DeleteFollowUp;
use App\Actions\FollowUps\SaveFollowUp;
use App\Actions\FollowUps\SetFollowUpStatus;
use App\Enums\FollowUpStatus;
use App\Enums\FollowUpType;
use App\Http\Requests\ChangeFollowUpStatusRequest;
use App\Http\Requests\SaveFollowUpRequest;
use App\Models\Client;
use App\Models\Enquiry;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\Enquiries\EnquiryOptions;
use App\Support\LikeTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request, EnquiryOptions $options): View
    {
        $this->authorize('viewAny', FollowUp::class);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $assignee = (string) $request->query('assigned_to', '');
        $related = (string) $request->query('related', '');
        $user = $request->user();

        $followUps = FollowUp::query()
            ->with(['assignee', 'followable'])
            ->where(function (Builder $query) use ($user) {
                $this->visibleParents($query, $user);
            })
            ->when($search !== '', fn (Builder $query) => $query->where('notes', 'like', LikeTerm::contains($search)))
            ->when($status === 'overdue', function (Builder $query) {
                $query->where('status', FollowUpStatus::Pending)->where('scheduled_at', '<', now());
            })
            ->when(in_array($status, ['pending', 'completed', 'cancelled'], true), fn (Builder $query) => $query->where('status', $status))
            ->when(FollowUpType::tryFrom($type) !== null, fn (Builder $query) => $query->where('type', $type))
            ->when($assignee !== '', fn (Builder $query) => $query->where('assigned_to', (int) $assignee))
            ->when($related === 'enquiry', fn (Builder $query) => $query->where('followable_type', (new Enquiry)->getMorphClass()))
            ->when($related === 'client', fn (Builder $query) => $query->where('followable_type', (new Client)->getMorphClass()))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('scheduled_at')
            ->paginate(20)
            ->withQueryString();

        return view('follow-ups.index', [
            'followUps' => $followUps,
            'staff' => $options->staff($assignee !== '' ? (int) $assignee : null),
            'search' => $search,
            'status' => $status,
            'type' => $type,
            'assignee' => $assignee,
            'related' => $related,
        ]);
    }

    public function store(SaveFollowUpRequest $request, SaveFollowUp $action): RedirectResponse
    {
        $parent = $request->resolvedParent();
        abort_unless($parent !== null, 404);
        $followUp = $action->execute($parent, $request->followUpAttributes(), $request->user());

        return $this->redirectTo($request, $followUp)->with('status', __('ui.follow_ups.saved'));
    }

    public function update(SaveFollowUpRequest $request, FollowUp $followUp, SaveFollowUp $action): RedirectResponse
    {
        $followUp->loadMissing('followable');
        $parent = $request->resolvedParent() ?? $followUp->followable;
        abort_unless($parent && $parent->is($followUp->followable), 404);

        $action->execute($parent, $request->followUpAttributes(), $request->user(), $followUp);

        return $this->redirectTo($request, $followUp)->with('status', __('ui.follow_ups.updated'));
    }

    public function status(ChangeFollowUpStatusRequest $request, FollowUp $followUp, SetFollowUpStatus $action): RedirectResponse
    {
        $action->execute($followUp, FollowUpStatus::from($request->string('follow_up_status')->toString()), $request->user());

        return $this->redirectTo($request, $followUp)->with('status', __('ui.follow_ups.status_saved'));
    }

    public function complete(Request $request, FollowUp $followUp, SetFollowUpStatus $action): RedirectResponse
    {
        $this->authorize('complete', $followUp);
        $action->execute($followUp, FollowUpStatus::Completed, $request->user());

        return $this->redirectTo($request, $followUp)->with('status', __('ui.follow_ups.completed'));
    }

    public function destroyMany(Request $request, DeleteFollowUp $action): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'confirm_delete' => ['accepted'],
        ], [], [
            'confirm_delete' => __('ui.follow_ups.confirm_delete'),
        ]);

        $rows = FollowUp::query()->whereIn('id', $data['ids'])->get();
        abort_if($rows->count() !== count(array_unique($data['ids'])), 404);

        foreach ($rows as $followUp) {
            $this->authorize('delete', $followUp);
        }

        $parent = null;

        foreach ($rows as $followUp) {
            $followUp->loadMissing('followable');
            $parent = $followUp->followable;
            $action->execute($followUp, $request->user());
        }

        if ($request->input('return_to') === 'parent' && $parent) {
            return redirect()->to($this->parentRoute($parent))->with('status', __('ui.follow_ups.deleted'));
        }

        return redirect()->to($this->portalRoute('follow-ups.index'))->with('status', __('ui.follow_ups.deleted'));
    }

    public function destroy(Request $request, FollowUp $followUp, DeleteFollowUp $action): RedirectResponse
    {
        $this->authorize('delete', $followUp);
        $request->validate([
            'confirm_delete' => ['accepted'],
        ], [], [
            'confirm_delete' => __('ui.follow_ups.confirm_delete'),
        ]);
        $followUp->loadMissing('followable');
        $parent = $followUp->followable;
        $action->execute($followUp, $request->user());

        if ($request->input('return_to') === 'index') {
            return redirect()->to($this->portalRoute('follow-ups.index'))->with('status', __('ui.follow_ups.deleted'));
        }

        return redirect()->to($this->parentRoute($parent))->with('status', __('ui.follow_ups.deleted'));
    }

    private function redirectTo(Request $request, FollowUp $followUp): RedirectResponse
    {
        if ($request->input('return_to') === 'index') {
            return redirect()->to($this->portalRoute('follow-ups.index'));
        }

        $followUp->loadMissing('followable');

        return redirect()->to($this->parentRoute($followUp->followable));
    }

    private function parentRoute(mixed $parent): string
    {
        if ($parent instanceof Enquiry) {
            return $this->portalRoute('enquiries.show', $parent);
        }

        if ($parent instanceof Client) {
            return $this->portalRoute('clients.show', $parent);
        }

        return $this->portalRoute('follow-ups.index');
    }

    private function visibleParents(Builder $query, User $user): void
    {
        $allowed = false;

        if ($user->can('viewAny', Enquiry::class)) {
            $query->orWhere('followable_type', (new Enquiry)->getMorphClass());
            $allowed = true;
        }

        if ($user->can('viewAny', Client::class)) {
            $query->orWhere('followable_type', (new Client)->getMorphClass());
            $allowed = true;
        }

        if (! $allowed) {
            $query->whereRaw('1 = 0');
        }
    }
}

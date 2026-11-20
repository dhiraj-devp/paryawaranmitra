<?php

namespace App\Http\Controllers;

use App\Actions\Clients\DeleteClient;
use App\Actions\Clients\SaveClient;
use App\Actions\Clients\SetClientStatus;
use App\Enums\ClientConstitution;
use App\Enums\RecordStatus;
use App\Http\Requests\ChangeRecordStatusRequest;
use App\Http\Requests\SaveClientRequest;
use App\Models\Client;
use App\Services\Clients\ClientHistory;
use App\Services\Enquiries\EnquiryOptions;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Client::class);

        $search = $this->term($request);
        $status = $this->requestedStatus($request);

        $clients = Client::query()
            ->withCount('industryUnits')
            ->withExists(['enquiries', 'workOrders', 'followUpRecords'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('legal_name', 'like', $like)
                        ->orWhere('trade_name', 'like', $like)
                        ->orWhere('pan', 'like', $like)
                        ->orWhere('gstin', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('district', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhereHas('industryUnits', fn ($query) => $query->where('name', 'like', $like));
                });
            })
            ->orderBy('legal_name')
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('clients.create', [
            'client' => new Client([
                'state' => 'Maharashtra',
                'constitution' => ClientConstitution::PrivateLimited,
            ]),
        ]);
    }

    public function store(SaveClientRequest $request, SaveClient $action): RedirectResponse
    {
        $client = $action->execute(
            $request->clientAttributes(),
            $request->user(),
            duplicateAcknowledged: $request->boolean('acknowledge_duplicate'),
        );

        return redirect()
            ->to($this->portalRoute('clients.show', $client))
            ->with('status', __('ui.clients.saved'));
    }

    public function show(Client $client, Request $request, ClientHistory $history, EnquiryOptions $options): View
    {
        $this->authorize('view', $client);

        $search = $this->term($request);
        $status = $this->requestedStatus($request);

        $units = $client->industryUnits()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($search !== null, fn ($query) => $query->where('name', 'like', LikeTerm::contains($search)))
            ->orderBy('name')
            ->paginate(15, ['*'], 'units')
            ->withQueryString();

        $client->load('followUpRecords.assignee');

        return view('clients.show', [
            'client' => $client,
            'units' => $units,
            'history' => $history->forClient($client),
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
            'staff' => $options->staff(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('update', $client);

        return view('clients.edit', [
            'client' => $client,
        ]);
    }

    public function update(SaveClientRequest $request, Client $client, SaveClient $action): RedirectResponse
    {
        $action->execute(
            $request->clientAttributes(),
            $request->user(),
            $client,
            $request->boolean('acknowledge_duplicate'),
        );

        return redirect()
            ->to($this->portalRoute('clients.show', $client))
            ->with('status', __('ui.clients.saved'));
    }

    public function status(ChangeRecordStatusRequest $request, Client $client, SetClientStatus $action): RedirectResponse
    {
        $action->execute($client, RecordStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('clients.show', $client))
            ->with('status', __('ui.clients.status_saved'));
    }

    private function term(Request $request): ?string
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return null;
        }

        return mb_substr($term, 0, 100);
    }

    private function requestedStatus(Request $request): ?RecordStatus
    {
        $status = (string) $request->query('status', '');

        return RecordStatus::tryFrom($status);
    }

    public function destroy(Request $request, Client $client, DeleteClient $action): RedirectResponse
    {
        $this->authorize('delete', $client);
        $request->validate(['confirm_delete' => ['accepted']]);
        $action->execute($client, $request->user());

        return redirect()->to($this->portalRoute('clients.index'))->with('status', __('ui.clients.deleted'));
    }

    public function destroyMany(Request $request, DeleteClient $action): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'confirm_delete' => ['accepted'],
        ]);

        $rows = Client::query()->whereIn('id', $data['ids'])->get();
        abort_if($rows->count() !== count(array_unique($data['ids'])), 404);

        foreach ($rows as $client) {
            $this->authorize('delete', $client);
        }

        foreach ($rows as $client) {
            $action->execute($client, $request->user());
        }

        return redirect()->to($this->portalRoute('clients.index'))->with('status', __('ui.clients.deleted'));
    }
}

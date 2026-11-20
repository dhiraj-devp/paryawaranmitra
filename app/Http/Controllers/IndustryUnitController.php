<?php

namespace App\Http\Controllers;

use App\Actions\Clients\SaveIndustryUnit;
use App\Actions\Clients\SetIndustryUnitStatus;
use App\Enums\RecordStatus;
use App\Http\Requests\ChangeRecordStatusRequest;
use App\Http\Requests\SaveIndustryUnitRequest;
use App\Models\Client;
use App\Models\IndustryUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IndustryUnitController extends Controller
{
    public function create(Client $client): View
    {
        $this->authorize('view', $client);
        $this->authorize('create', IndustryUnit::class);

        abort_unless($client->status === RecordStatus::Active, 403);

        return view('industry-units.create', [
            'client' => $client,
            'unit' => new IndustryUnit([
                'site_state' => 'Maharashtra',
                'industry_category' => 'chemical',
            ]),
        ]);
    }

    public function store(SaveIndustryUnitRequest $request, Client $client, SaveIndustryUnit $action): RedirectResponse
    {
        $this->authorize('view', $client);

        $unit = $action->execute($client, $request->unitAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('clients.units.show', [$client, $unit]))
            ->with('status', __('ui.units.saved'));
    }

    public function show(Client $client, IndustryUnit $unit): View
    {
        $this->owned($client, $unit);
        $this->authorize('view', $client);
        $this->authorize('view', $unit);

        return view('industry-units.show', [
            'client' => $client,
            'unit' => $unit,
        ]);
    }

    public function edit(Client $client, IndustryUnit $unit): View
    {
        $this->owned($client, $unit);
        $this->authorize('update', $unit);

        return view('industry-units.edit', [
            'client' => $client,
            'unit' => $unit,
        ]);
    }

    public function update(SaveIndustryUnitRequest $request, Client $client, IndustryUnit $unit, SaveIndustryUnit $action): RedirectResponse
    {
        $this->owned($client, $unit);

        $action->execute($client, $request->unitAttributes(), $request->user(), $unit);

        return redirect()
            ->to($this->portalRoute('clients.units.show', [$client, $unit]))
            ->with('status', __('ui.units.saved'));
    }

    public function status(ChangeRecordStatusRequest $request, Client $client, IndustryUnit $unit, SetIndustryUnitStatus $action): RedirectResponse
    {
        $this->owned($client, $unit);

        $action->execute($unit, RecordStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('clients.units.show', [$client, $unit]))
            ->with('status', __('ui.units.status_saved'));
    }

    private function owned(Client $client, IndustryUnit $unit): void
    {
        abort_unless($unit->client_id === $client->id, 404);
    }
}

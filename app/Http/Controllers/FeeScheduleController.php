<?php

namespace App\Http\Controllers;

use App\Actions\Fees\SaveFeeSchedule;
use App\Actions\Fees\SetFeeScheduleStatus;
use App\Enums\RecordStatus;
use App\Http\Requests\ChangeCatalogStatusRequest;
use App\Http\Requests\SaveFeeScheduleRequest;
use App\Models\FeeSchedule;
use App\Models\Service;
use App\Services\Fees\ApplicableFee;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FeeScheduleController extends Controller
{
    public function index(Request $request, ApplicableFee $applicable): View
    {
        $this->authorize('viewAny', FeeSchedule::class);

        $search = $this->term($request);
        $status = RecordStatus::tryFrom((string) $request->query('status', ''));
        $serviceId = $request->integer('service');
        $coverage = (string) $request->query('coverage', '');
        $today = today()->toDateString();

        $schedules = FeeSchedule::query()
            ->with('service')
            ->when($serviceId > 0, fn ($query) => $query->where('service_id', $serviceId))
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($coverage === 'current', function ($query) use ($today) {
                $query->whereDate('effective_from', '<=', $today)
                    ->where(function ($query) use ($today) {
                        $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $today);
                    });
            })
            ->when($coverage === 'upcoming', fn ($query) => $query->whereDate('effective_from', '>', $today))
            ->when($coverage === 'past', fn ($query) => $query->whereNotNull('effective_until')->whereDate('effective_until', '<', $today))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->whereHas('service', function ($query) use ($like) {
                    $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                });
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $lookupService = null;
        $lookupSchedule = null;
        $lookupDate = null;

        if ($request->filled('lookup_service') && $request->filled('lookup_on')) {
            $lookupService = Service::query()->find($request->integer('lookup_service'));
            $lookupDate = $this->date($request->query('lookup_on'));

            if ($lookupService !== null && $lookupDate !== null) {
                $lookupSchedule = $applicable->on($lookupService, $lookupDate);
            }
        }

        return view('fees.index', [
            'schedules' => $schedules,
            'services' => Service::query()->orderBy('name')->get(['id', 'code', 'name', 'status']),
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
            'serviceId' => $serviceId > 0 ? $serviceId : '',
            'coverage' => in_array($coverage, ['current', 'upcoming', 'past'], true) ? $coverage : '',
            'lookupService' => $lookupService,
            'lookupSchedule' => $lookupSchedule,
            'lookupDate' => $lookupDate,
            'lookupSubmitted' => $lookupService !== null && $lookupDate !== null,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', FeeSchedule::class);

        $schedule = new FeeSchedule([
            'effective_from' => today()->toDateString(),
            'government_fee' => null,
            'consultancy_fee' => null,
        ]);

        return view('fees.create', [
            'schedule' => $schedule,
            'services' => Service::query()->where('status', RecordStatus::Active)->orderBy('name')->get(),
            'selectedService' => $request->integer('service') ?: null,
        ]);
    }

    public function store(SaveFeeScheduleRequest $request, SaveFeeSchedule $action): RedirectResponse
    {
        $schedule = $action->execute(
            $request->scheduleAttributes(),
            $request->user(),
            $request->service(),
        );

        return redirect()
            ->to($this->portalRoute('fees.show', $schedule))
            ->with('status', __('ui.fees.saved'));
    }

    public function show(FeeSchedule $feeSchedule): View
    {
        $this->authorize('view', $feeSchedule);

        $feeSchedule->load(['service', 'creator', 'editor']);

        return view('fees.show', [
            'schedule' => $feeSchedule,
        ]);
    }

    public function edit(FeeSchedule $feeSchedule): View
    {
        $this->authorize('update', $feeSchedule);

        $feeSchedule->load('service');

        return view('fees.edit', [
            'schedule' => $feeSchedule,
        ]);
    }

    public function update(SaveFeeScheduleRequest $request, FeeSchedule $feeSchedule, SaveFeeSchedule $action): RedirectResponse
    {
        $feeSchedule->loadMissing('service');

        $action->execute(
            $request->scheduleAttributes(),
            $request->user(),
            $feeSchedule->service,
            $feeSchedule,
        );

        return redirect()
            ->to($this->portalRoute('fees.show', $feeSchedule))
            ->with('status', __('ui.fees.saved'));
    }

    public function status(ChangeCatalogStatusRequest $request, FeeSchedule $feeSchedule, SetFeeScheduleStatus $action): RedirectResponse
    {
        $action->execute($feeSchedule, RecordStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('fees.show', $feeSchedule))
            ->with('status', __('ui.fees.status_saved'));
    }

    private function term(Request $request): ?string
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return null;
        }

        return mb_substr($term, 0, 100);
    }

    private function date(mixed $value): ?Carbon
    {
        $parsed = Carbon::createFromFormat('Y-m-d', (string) $value);

        if ($parsed === false || $parsed->format('Y-m-d') !== (string) $value) {
            return null;
        }

        return $parsed;
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveService;
use App\Actions\Catalog\SetServiceStatus;
use App\Enums\RecordStatus;
use App\Http\Requests\ChangeCatalogStatusRequest;
use App\Http\Requests\SaveServiceRequest;
use App\Models\Service;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Service::class);

        $search = $this->term($request);
        $status = $this->requestedStatus($request);
        $category = trim((string) $request->query('category', ''));

        $services = Service::query()
            ->withCount('industries')
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('code', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('services.index', [
            'services' => $services,
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
            'category' => $category,
            'categories' => Service::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Service::class);

        $service = new Service;
        $service->setRelation('industries', collect());

        return view('services.create', [
            'service' => $service,
            'categories' => Service::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function store(SaveServiceRequest $request, SaveService $action): RedirectResponse
    {
        $service = $action->execute($request->serviceAttributes(), $request->industries(), $request->user());

        return redirect()
            ->to($this->portalRoute('services.show', $service))
            ->with('status', __('ui.services.saved'));
    }

    public function show(Service $service): View
    {
        $this->authorize('view', $service);

        $service->load(['industries', 'creator', 'editor']);

        return view('services.show', [
            'service' => $service,
        ]);
    }

    public function edit(Service $service): View
    {
        $this->authorize('update', $service);

        $service->load('industries');

        return view('services.edit', [
            'service' => $service,
            'categories' => Service::query()->distinct()->orderBy('category')->pluck('category'),
        ]);
    }

    public function update(SaveServiceRequest $request, Service $service, SaveService $action): RedirectResponse
    {
        $action->execute($request->serviceAttributes(), $request->industries(), $request->user(), $service);

        return redirect()
            ->to($this->portalRoute('services.show', $service))
            ->with('status', __('ui.services.saved'));
    }

    public function status(ChangeCatalogStatusRequest $request, Service $service, SetServiceStatus $action): RedirectResponse
    {
        $action->execute($service, RecordStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('services.show', $service))
            ->with('status', __('ui.services.status_saved'));
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
        return RecordStatus::tryFrom((string) $request->query('status', ''));
    }
}

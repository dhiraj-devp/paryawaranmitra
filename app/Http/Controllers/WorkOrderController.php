<?php

namespace App\Http\Controllers;

use App\Actions\WorkOrders\AssignWorkOrder;
use App\Actions\WorkOrders\SaveWorkOrder;
use App\Actions\WorkOrders\SetWorkOrderStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\ChangeWorkOrderStatusRequest;
use App\Http\Requests\SaveWorkOrderRequest;
use App\Models\WorkOrder;
use App\Services\Audit\SubjectHistory;
use App\Services\Enquiries\EnquiryOptions;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkOrder::class);

        $search = $this->term($request);
        $status = WorkOrderStatus::tryFrom((string) $request->query('status', ''));

        $workOrders = WorkOrder::query()
            ->with(['client', 'industryUnit', 'service', 'assignee'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)
                        ->orWhereHas('client', fn ($query) => $query->where('legal_name', 'like', $like))
                        ->orWhereHas('industryUnit', fn ($query) => $query->where('name', 'like', $like))
                        ->orWhereHas('service', fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
                });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('work-orders.index', [
            'workOrders' => $workOrders,
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
        ]);
    }

    public function show(Request $request, WorkOrder $workOrder, EnquiryOptions $options, SubjectHistory $history): View
    {
        $this->authorize('view', $workOrder);

        $workOrder->load([
            'enquiry',
            'client',
            'industryUnit',
            'service',
            'assignee',
            'creator',
            'editor',
        ]);

        $quotations = collect();

        if ($request->user()?->hasPermission('quotations.view')) {
            $quotations = $workOrder->quotations()->with('currentRevision')->orderByDesc('id')->get();
        }

        return view('work-orders.show', [
            'workOrder' => $workOrder,
            'staff' => $options->staff($workOrder->assigned_to),
            'history' => $history->for($workOrder),
            'quotations' => $quotations,
        ]);
    }

    public function edit(WorkOrder $workOrder): View
    {
        $this->authorize('update', $workOrder);

        return view('work-orders.edit', [
            'workOrder' => $workOrder,
        ]);
    }

    public function update(SaveWorkOrderRequest $request, WorkOrder $workOrder, SaveWorkOrder $action): RedirectResponse
    {
        $action->execute($workOrder, $request->workOrderAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('work-orders.show', $workOrder))
            ->with('status', __('ui.work_orders.saved'));
    }

    public function status(ChangeWorkOrderStatusRequest $request, WorkOrder $workOrder, SetWorkOrderStatus $action): RedirectResponse
    {
        $action->execute($workOrder, WorkOrderStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('work-orders.show', $workOrder))
            ->with('status', __('ui.work_orders.status_saved'));
    }

    public function assign(AssignStaffRequest $request, WorkOrder $workOrder, AssignWorkOrder $action): RedirectResponse
    {
        $assignee = $request->input('assigned_to');

        $action->execute($workOrder, $assignee === null ? null : (int) $assignee, $request->user());

        return redirect()
            ->to($this->portalRoute('work-orders.show', $workOrder))
            ->with('status', __('ui.work_orders.assignment_saved'));
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

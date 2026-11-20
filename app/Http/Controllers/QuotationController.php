<?php

namespace App\Http\Controllers;

use App\Actions\Quotations\CreateQuotation;
use App\Actions\Quotations\DeliverQuotationPdf;
use App\Actions\Quotations\ReviseQuotation;
use App\Actions\Quotations\SetQuotationStatus;
use App\Enums\ChargeSide;
use App\Enums\QuotationLineType;
use App\Enums\QuotationStatus;
use App\Enums\WorkOrderStatus;
use App\Http\Requests\ChangeQuotationStatusRequest;
use App\Http\Requests\SaveQuotationRequest;
use App\Models\Quotation;
use App\Models\QuotationRevision;
use App\Models\WorkOrder;
use App\Services\Audit\SubjectHistory;
use App\Services\Fees\ApplicableFee;
use App\Services\Quotations\TaxSettings;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Quotation::class);

        $search = $this->term($request);
        $status = QuotationStatus::tryFrom((string) $request->query('status', ''));

        $quotations = Quotation::query()
            ->with(['workOrder.client', 'workOrder.industryUnit', 'workOrder.service', 'currentRevision'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('quotation_number', 'like', $like)
                        ->orWhereHas('workOrder', function ($query) use ($like) {
                            $query->where('number', 'like', $like)
                                ->orWhereHas('client', fn ($query) => $query->where('legal_name', 'like', $like))
                                ->orWhereHas('industryUnit', fn ($query) => $query->where('name', 'like', $like))
                                ->orWhereHas('service', fn ($query) => $query->where('name', 'like', $like)->orWhere('code', 'like', $like));
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', [
            'quotations' => $quotations,
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
        ]);
    }

    public function create(Request $request, ApplicableFee $fees): View
    {
        $this->authorize('create', Quotation::class);

        $workOrders = $this->openWorkOrders();
        $selected = $workOrders->firstWhere('id', (int) old('work_order_id', $request->query('work_order', 0)));

        return view('quotations.create', [
            'workOrders' => $workOrders,
            'selected' => $selected,
            'schedule' => $this->scheduleFor($fees, $selected, (string) old('issue_date', now()->toDateString())),
            'customRows' => $this->customRows(),
            'taxPending' => app(TaxSettings::class)->pending(),
        ]);
    }

    public function store(SaveQuotationRequest $request, CreateQuotation $action): RedirectResponse
    {
        $workOrder = WorkOrder::query()->findOrFail($request->integer('work_order_id'));
        $quotation = $action->execute($workOrder, $request->quotationInput(), $request->user());

        return redirect()
            ->to($this->portalRoute('quotations.show', $quotation))
            ->with('status', __('ui.quotations.created'));
    }

    public function show(Quotation $quotation, SubjectHistory $history, TaxSettings $tax): View
    {
        $this->authorize('view', $quotation);

        $quotation->load([
            'workOrder.client',
            'workOrder.industryUnit',
            'workOrder.service',
            'currentRevision.lines',
            'acceptedRevision',
            'revisions',
            'creator',
        ]);

        return view('quotations.show', [
            'quotation' => $quotation,
            'history' => $history->for($quotation),
            'taxPending' => $tax->pending(),
        ]);
    }

    public function revise(Quotation $quotation, ApplicableFee $fees): View
    {
        $this->authorize('update', $quotation);
        abort_unless($quotation->status->canRevise(), 403);

        $quotation->load([
            'workOrder.client',
            'workOrder.industryUnit',
            'workOrder.service',
            'currentRevision.lines',
            'acceptedRevision',
        ]);

        $issueDate = (string) old('issue_date', $quotation->currentRevision?->issue_date?->toDateString());

        return view('quotations.revise', [
            'quotation' => $quotation,
            'schedule' => $this->scheduleFor($fees, $quotation->workOrder, $issueDate),
            'customRows' => $this->customRows($quotation->currentRevision),
            'taxPending' => app(TaxSettings::class)->pending(),
        ]);
    }

    public function storeRevision(SaveQuotationRequest $request, Quotation $quotation, ReviseQuotation $action): RedirectResponse
    {
        $action->execute($quotation, $request->quotationInput(), $request->user());

        return redirect()
            ->to($this->portalRoute('quotations.show', $quotation))
            ->with('status', __('ui.quotations.revised'));
    }

    public function status(ChangeQuotationStatusRequest $request, Quotation $quotation, SetQuotationStatus $action): RedirectResponse
    {
        $action->execute(
            $quotation,
            QuotationStatus::from($request->string('status')->toString()),
            $request->input('reason'),
            $request->user(),
        );

        return redirect()
            ->to($this->portalRoute('quotations.show', $quotation))
            ->with('status', __('ui.quotations.status_saved'));
    }

    public function showRevision(Quotation $quotation, QuotationRevision $revision, TaxSettings $tax): View
    {
        $this->authorize('view', $quotation);
        abort_unless($revision->quotation_id === $quotation->id, 404);

        $quotation->load([
            'workOrder.client',
            'workOrder.industryUnit',
            'workOrder.service',
            'acceptedRevision',
        ]);
        $revision->load('lines');

        return view('quotations.revision', [
            'quotation' => $quotation,
            'revision' => $revision,
            'taxPending' => $tax->pending(),
        ]);
    }

    public function compare(Request $request, Quotation $quotation): View
    {
        $this->authorize('view', $quotation);

        $revisions = $quotation->revisions()->with('lines')->orderBy('revision_number')->get();
        $from = $revisions->firstWhere('revision_number', (int) $request->query('from')) ?? $revisions->first();
        $to = $revisions->firstWhere('revision_number', (int) $request->query('to')) ?? $revisions->last();
        $rows = [];
        $left = $from?->lines->values() ?? collect();
        $right = $to?->lines->values() ?? collect();
        $count = max($left->count(), $right->count());

        for ($index = 0; $index < $count; $index++) {
            $rows[] = [
                'left' => $left[$index] ?? null,
                'right' => $right[$index] ?? null,
            ];
        }

        return view('quotations.compare', [
            'quotation' => $quotation,
            'revisions' => $revisions,
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
        ]);
    }

    public function pdf(Quotation $quotation, QuotationRevision $revision, DeliverQuotationPdf $action): Response
    {
        $this->authorize('view', $quotation);

        $path = $action->execute($quotation, $revision, request()->user());
        $name = $quotation->quotation_number.'-R'.$revision->revision_number.'.pdf';

        return Storage::disk('quotations')->download($path, $name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return Collection<int, WorkOrder>
     */
    private function openWorkOrders()
    {
        return WorkOrder::query()
            ->with(['client', 'service'])
            ->where('status', '!=', WorkOrderStatus::Closed)
            ->orderByDesc('id')
            ->get();
    }

    private function scheduleFor(ApplicableFee $fees, ?WorkOrder $workOrder, string $issueDate): mixed
    {
        if ($workOrder?->service === null) {
            return null;
        }

        try {
            $date = Carbon::parse($issueDate);
        } catch (\Throwable) {
            return null;
        }

        return $fees->on($workOrder->service, $date);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customRows(?QuotationRevision $revision = null): array
    {
        $rows = [];

        if (is_array(old('custom_lines'))) {
            $rows = array_values(old('custom_lines'));
        } elseif ($revision !== null) {
            foreach ($revision->lines as $line) {
                if ($line->line_type !== QuotationLineType::Custom) {
                    continue;
                }

                $rows[] = [
                    'description' => $line->description,
                    'charge_side' => $line->charge_side?->value ?? '',
                    'quantity' => $line->quantity,
                    'amount' => $line->charge_side === ChargeSide::Government ? $line->government_fee : $line->consultancy_fee,
                    'discount_amount' => $line->discount_amount,
                ];
            }
        }

        while (count($rows) < 3) {
            $rows[] = [
                'description' => '',
                'charge_side' => '',
                'quantity' => '1',
                'amount' => '',
                'discount_amount' => '0.00',
            ];
        }

        return $rows;
    }

    private function term(Request $request): ?string
    {
        $term = trim((string) $request->query('q', ''));

        return mb_strlen($term) < 2 ? null : $term;
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\Enquiries\AssignEnquiry;
use App\Actions\Enquiries\DeleteEnquiry;
use App\Actions\Enquiries\RecordEnquiryFollowUp;
use App\Actions\Enquiries\SaveEnquiry;
use App\Actions\Enquiries\SetEnquiryStatus;
use App\Actions\WorkOrders\ConvertEnquiry;
use App\Enums\EnquirySource;
use App\Enums\EnquiryStatus;
use App\Http\Requests\AssignStaffRequest;
use App\Http\Requests\ChangeEnquiryStatusRequest;
use App\Http\Requests\ConvertEnquiryRequest;
use App\Http\Requests\RecordEnquiryFollowUpRequest;
use App\Http\Requests\SaveEnquiryRequest;
use App\Models\Enquiry;
use App\Services\Audit\SubjectHistory;
use App\Services\Enquiries\EnquiryOptions;
use App\Support\LikeTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request, EnquiryOptions $options): View
    {
        $this->authorize('viewAny', Enquiry::class);

        $search = $this->term($request);
        $status = EnquiryStatus::tryFrom((string) $request->query('status', ''));
        $source = EnquirySource::tryFrom((string) $request->query('source', ''));
        $followUp = (string) $request->query('follow_up', '');

        $enquiries = Enquiry::query()
            ->with(['client', 'service', 'assignee'])
            ->withExists(['workOrder', 'followUps', 'followUpRecords'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when($source !== null, fn ($query) => $query->where('source', $source))
            ->when($followUp === 'overdue', function ($query) {
                $query->whereDate('next_follow_up_on', '<', today())
                    ->whereNotIn('status', [EnquiryStatus::Converted->value, EnquiryStatus::Lost->value]);
            })
            ->when($followUp === 'upcoming', fn ($query) => $query->whereDate('next_follow_up_on', '>=', today()))
            ->when($followUp === 'none', fn ($query) => $query->whereNull('next_follow_up_on'))
            ->when($search !== null, function ($query) use ($search) {
                $like = LikeTerm::contains($search);

                $query->where(function ($query) use ($like) {
                    $query->where('number', 'like', $like)
                        ->orWhere('contact_name', 'like', $like)
                        ->orWhere('mobile', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('client', fn ($query) => $query->where('legal_name', 'like', $like));
                });
            })
            ->orderByDesc('enquiry_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('enquiries.index', [
            'enquiries' => $enquiries,
            'search' => $search ?? '',
            'status' => $status?->value ?? '',
            'source' => $source?->value ?? '',
            'followUp' => in_array($followUp, ['overdue', 'upcoming', 'none'], true) ? $followUp : '',
            'enquiry' => new Enquiry([
                'enquiry_date' => today(),
                'source' => EnquirySource::Phone,
            ]),
            'clients' => $options->clients(),
            'units' => $options->units(),
            'services' => $options->services(),
            'staff' => $options->staff(),
            'openEnquiry' => $request->boolean('new') || (bool) $request->session()->get('errors')?->getBag('default')->hasAny(['enquiry_date', 'source', 'contact_name', 'mobile', 'email', 'client_id', 'industry_unit_id', 'service_id', 'assigned_to', 'requirement', 'internal_notes']),
        ]);
    }

    public function create(EnquiryOptions $options): View
    {
        $this->authorize('create', Enquiry::class);

        return view('enquiries.create', [
            'enquiry' => new Enquiry([
                'enquiry_date' => today(),
                'source' => EnquirySource::Phone,
            ]),
            'clients' => $options->clients(),
            'units' => $options->units(),
            'services' => $options->services(),
            'staff' => $options->staff(),
        ]);
    }

    public function store(SaveEnquiryRequest $request, SaveEnquiry $action): RedirectResponse
    {
        $enquiry = $action->execute($request->enquiryAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('enquiries.show', $enquiry))
            ->with('status', __('ui.enquiries.saved'));
    }

    public function show(Enquiry $enquiry, EnquiryOptions $options, SubjectHistory $history): View
    {
        $this->authorize('view', $enquiry);

        $enquiry->load([
            'client',
            'industryUnit',
            'service',
            'assignee',
            'creator',
            'editor',
            'workOrder',
            'followUpRecords.assignee',
        ]);

        return view('enquiries.show', [
            'enquiry' => $enquiry,
            'staff' => $options->staff($enquiry->assigned_to),
            'clients' => $options->clients(),
            'units' => $options->units($enquiry->industry_unit_id),
            'services' => $options->services(),
            'history' => $history->for($enquiry),
        ]);
    }

    public function edit(Enquiry $enquiry, EnquiryOptions $options): View
    {
        $this->authorize('update', $enquiry);

        return view('enquiries.edit', [
            'enquiry' => $enquiry,
            'clients' => $options->clients($enquiry->client_id),
            'units' => $options->units($enquiry->industry_unit_id),
            'services' => $options->services($enquiry->service_id),
            'staff' => $options->staff($enquiry->assigned_to),
        ]);
    }

    public function update(SaveEnquiryRequest $request, Enquiry $enquiry, SaveEnquiry $action): RedirectResponse
    {
        $action->execute($request->enquiryAttributes(), $request->user(), $enquiry);

        return redirect()
            ->to($this->portalRoute('enquiries.show', $enquiry))
            ->with('status', __('ui.enquiries.saved'));
    }

    public function status(ChangeEnquiryStatusRequest $request, Enquiry $enquiry, SetEnquiryStatus $action): RedirectResponse
    {
        $action->execute($enquiry, EnquiryStatus::from($request->string('status')->toString()), $request->user());

        return redirect()
            ->to($this->portalRoute('enquiries.show', $enquiry))
            ->with('status', __('ui.enquiries.status_saved'));
    }

    public function assign(AssignStaffRequest $request, Enquiry $enquiry, AssignEnquiry $action): RedirectResponse
    {
        $assignee = $request->input('assigned_to');

        $action->execute($enquiry, $assignee === null ? null : (int) $assignee, $request->user());

        return redirect()
            ->to($this->portalRoute('enquiries.show', $enquiry))
            ->with('status', __('ui.enquiries.assignment_saved'));
    }

    public function followUp(RecordEnquiryFollowUpRequest $request, Enquiry $enquiry, RecordEnquiryFollowUp $action): RedirectResponse
    {
        $action->execute($enquiry, $request->followUpAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('enquiries.show', $enquiry))
            ->with('status', __('ui.follow_ups.saved'));
    }

    public function convert(ConvertEnquiryRequest $request, Enquiry $enquiry, ConvertEnquiry $action): RedirectResponse
    {
        $workOrder = $action->execute($enquiry, $request->conversionAttributes(), $request->user());

        return redirect()
            ->to($this->portalRoute('work-orders.show', $workOrder))
            ->with('status', __('ui.work_orders.converted'));
    }

    public function destroy(Request $request, Enquiry $enquiry, DeleteEnquiry $action): RedirectResponse
    {
        $this->authorize('delete', $enquiry);
        $request->validate(['confirm_delete' => ['accepted']]);
        $action->execute($enquiry, $request->user());

        return redirect()->to($this->portalRoute('enquiries.index'))->with('status', __('ui.enquiries.deleted'));
    }

    public function destroyMany(Request $request, DeleteEnquiry $action): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'confirm_delete' => ['accepted'],
        ]);

        $rows = Enquiry::query()->whereIn('id', $data['ids'])->get();
        abort_if($rows->count() !== count(array_unique($data['ids'])), 404);

        foreach ($rows as $enquiry) {
            $this->authorize('delete', $enquiry);
        }

        foreach ($rows as $enquiry) {
            $action->execute($enquiry, $request->user());
        }

        return redirect()->to($this->portalRoute('enquiries.index'))->with('status', __('ui.enquiries.deleted'));
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

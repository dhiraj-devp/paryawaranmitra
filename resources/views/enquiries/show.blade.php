@php
    $portal = auth()->user()->portal->value;
    $converted = $enquiry->status === \App\Enums\EnquiryStatus::Converted;
    $openConvert = $errors->hasAny(['client_id', 'industry_unit_id', 'service_id', 'work_description', 'start_date', 'target_completion_date', 'internal_notes', 'confirm_conversion', 'enquiry']);
    $openStatus = $errors->hasAny(['status', 'confirm_status']);
    $openAssign = $errors->has('confirm_assignment') || ($errors->has('assigned_to') && filled(old('confirm_assignment')));
    $serviceLabel = $enquiry->service ? $enquiry->service->code.' — '.$enquiry->service->name : null;
    $staffNames = $staff->pluck('name', 'id');
    $dash = fn ($value) => filled($value) ? $value : '—';
    $activityNote = function ($log) use ($staffNames): ?string {
        $new = $log->new_values ?? [];
        $old = $log->old_values ?? [];

        if ($log->action === 'enquiry.assignment_changed') {
            $label = function ($id) use ($staffNames) {
                if ($id === null || $id === '') {
                    return __('ui.enquiries.unassigned');
                }

                return $staffNames[(int) $id] ?? null;
            };
            $from = $label($old['assigned_to'] ?? null);
            $to = $label($new['assigned_to'] ?? null);

            return ($from && $to) ? $from.' → '.$to : $to;
        }

        $parts = [];

        if (isset($new['status'])) {
            $from = \App\Enums\EnquiryStatus::tryFrom((string) ($old['status'] ?? ''));
            $to = \App\Enums\EnquiryStatus::tryFrom((string) $new['status']);
            if ($from && $to) {
                $parts[] = $from->label().' → '.$to->label();
            } elseif ($to) {
                $parts[] = $to->label();
            }
        }

        if (isset($new['method'])) {
            $method = \App\Enums\FollowUpMethod::tryFrom((string) $new['method']);
            if ($method) {
                $parts[] = $method->label();
            }
        }

        if (filled($new['outcome'] ?? null)) {
            $parts[] = (string) $new['outcome'];
        }

        if (filled($new['next_follow_up_on'] ?? null)) {
            $parts[] = __('ui.enquiries.next_follow_up').' '.\Illuminate\Support\Carbon::parse($new['next_follow_up_on'])->format('d-m-Y');
        }

        if (filled($new['work_order_number'] ?? null)) {
            $parts[] = (string) $new['work_order_number'];
        }

        return $parts === [] ? null : implode(' · ', $parts);
    };
@endphp

@extends('layouts.app')

@section('title', $enquiry->number)

@section('content')
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div class="min-w-0">
            <a href="{{ route($portal.'.enquiries.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-forest-800 hover:underline">
                <x-ui.icon name="chevron-left" class="h-4 w-4" />
                {{ __('ui.back') }}
            </a>
            <nav class="mb-1 mt-3 text-sm text-muted" aria-label="{{ __('ui.enquiries.title') }}">
                <a href="{{ route($portal.'.enquiries.index') }}" class="text-forest-800 hover:underline">{{ __('ui.enquiries.title') }}</a>
                <span aria-hidden="true"> / </span>
                <span class="text-ink">{{ $enquiry->number }}</span>
            </nav>
            <h1 class="mt-2 text-xl font-semibold tracking-wide text-ink">{{ $enquiry->number }}</h1>
            <p class="mt-3 text-sm text-muted">{{ $enquiry->contact_name }}</p>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                <p>
                    <span class="text-muted">{{ __('ui.enquiries.email') }}</span>
                    @if (filled($enquiry->email))
                        <a href="mailto:{{ $enquiry->email }}" class="font-medium text-forest-800 hover:underline">{{ $enquiry->email }}</a>
                    @else
                        <span class="font-medium text-ink">—</span>
                    @endif
                </p>
                <p>
                    <span class="text-muted">{{ __('ui.enquiries.mobile') }}</span>
                    <span class="font-medium text-ink">{{ $dash($enquiry->mobile) }}</span>
                </p>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @can('update', $enquiry)
                @unless ($converted)
                    <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('open-panel', 'change-status')">{{ __('ui.status.change') }}</x-ui.button>
                @endunless
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('open-panel', 'assign-staff')">{{ __('ui.enquiries.assign') }}</x-ui.button>
                <x-ui.button variant="secondary" :href="route($portal.'.enquiries.edit', $enquiry)">{{ __('ui.enquiries.edit') }}</x-ui.button>
            @endcan
            @can('convert', $enquiry)
                <x-ui.button type="button" x-on:click="$dispatch('open-panel', 'convert-enquiry')">{{ __('ui.enquiries.convert') }}</x-ui.button>
            @endcan
        </div>
    </div>

    <div class="grid items-start gap-4 xl:grid-cols-3">
        <div class="grid gap-4 xl:col-span-2">
            <x-ui.card>
                <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xs text-muted">{{ __('ui.enquiries.requirement') }}</h2>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $dash($enquiry->requirement) }}</p>
                    </div>
                    <x-ui.badge class="w-fit px-2.5 py-1 text-sm" :tone="$enquiry->status->tone()">{{ $enquiry->status->label() }}</x-ui.badge>
                </div>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 border-t border-line px-4 py-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.date') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $dash($enquiry->enquiry_date?->format('d-m-Y')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.source') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $enquiry->source->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.assignee') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $dash($enquiry->assignee?->name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.client') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $dash($enquiry->client?->legal_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.unit') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $dash($enquiry->industryUnit?->name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">{{ __('ui.enquiries.service') }}</dt>
                        <dd class="mt-0.5 text-sm text-ink">{{ $dash($serviceLabel) }}</dd>
                    </div>
                    @if ($enquiry->workOrder)
                        <div>
                            <dt class="text-xs text-muted">{{ __('ui.work_orders.number') }}</dt>
                            <dd class="mt-0.5 text-sm">
                                <a href="{{ route($portal.'.work-orders.show', $enquiry->workOrder) }}" class="font-medium text-forest-800 hover:underline">{{ $enquiry->workOrder->number }}</a>
                            </dd>
                        </div>
                    @endif
                </dl>
                @if (filled($enquiry->internal_notes))
                    <div class="mx-4 mb-4 rounded-md border border-line bg-canvas px-3 py-2">
                        <h3 class="text-xs font-medium text-muted">{{ __('ui.enquiries.notes') }}</h3>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $enquiry->internal_notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            @include('follow-ups._card', ['parent' => $enquiry, 'records' => $enquiry->followUpRecords, 'staff' => $staff, 'returnTo' => 'parent'])
        </div>

        <x-ui.card>
            <div class="border-b border-line px-4 py-2.5">
                <h2 class="text-sm font-semibold text-ink">{{ __('ui.enquiries.activity') }}</h2>
            </div>
            @if ($history->isEmpty())
                <p class="px-4 py-3 text-sm text-muted">{{ __('ui.enquiries.no_history') }}</p>
            @else
                <ol class="px-4 py-3">
                    @foreach ($history as $log)
                        @php($note = $activityNote($log))
                        <li class="relative border-l border-line pb-4 pl-4 last:pb-0">
                            <span class="absolute -left-1 top-1.5 h-2 w-2 rounded-full bg-forest-700"></span>
                            <p class="text-sm font-medium text-ink">{{ $log->label() }}</p>
                            @if ($note)
                                <p class="mt-0.5 text-sm text-muted">{{ $note }}</p>
                            @endif
                            <p class="mt-0.5 text-xs text-muted">{{ $log->actor?->name ?? __('ui.audit.system') }} · {{ $log->created_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.card>
    </div>

    @can('update', $enquiry)
        @unless ($converted)
            <x-ui.panel name="change-status" :title="__('ui.status.change')" :open="$openStatus">
                <form method="post" action="{{ route($portal.'.enquiries.status', $enquiry) }}" class="grid gap-3">
                    @csrf
                    <x-ui.select id="enquiry-status-change" name="status" :label="__('ui.status.label')" :error="$errors->first('status')">
                        @foreach (\App\Enums\EnquiryStatus::cases() as $option)
                            @continue($option === \App\Enums\EnquiryStatus::Converted)
                            <option value="{{ $option->value }}" @selected(old('status', $enquiry->status->value) === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </x-ui.select>
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="confirm_status" value="1" class="mt-0.5" @checked(old('confirm_status'))>
                        <span>{{ __('ui.status.confirm') }}</span>
                    </label>
                    @error('confirm_status')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <div>
                        <x-ui.button variant="secondary">{{ __('ui.status.change') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.panel>
        @endunless

        <x-ui.panel name="assign-staff" :title="__('ui.enquiries.assign')" :open="$openAssign">
            <form method="post" action="{{ route($portal.'.enquiries.assign', $enquiry) }}" class="grid gap-3">
                @csrf
                <x-ui.select id="enquiry-assign" name="assigned_to" :label="__('ui.enquiries.assignee')" :error="$errors->first('assigned_to')">
                    <option value="">{{ __('ui.enquiries.unassigned') }}</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" @selected((string) old('assigned_to', $enquiry->assigned_to) === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </x-ui.select>
                <label class="flex items-start gap-2 text-sm text-ink">
                    <input type="checkbox" name="confirm_assignment" value="1" class="mt-0.5" @checked(old('confirm_assignment'))>
                    <span>{{ __('ui.enquiries.confirm_assignment') }}</span>
                </label>
                @error('confirm_assignment')
                    <p class="text-sm text-red-700">{{ $message }}</p>
                @enderror
                <div>
                    <x-ui.button variant="secondary">{{ __('ui.enquiries.assign') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    @endcan

    @can('convert', $enquiry)
        <x-ui.panel name="convert-enquiry" :title="__('ui.enquiries.convert')" :open="$openConvert">
            <p class="mb-3 text-sm text-muted">{{ __('ui.enquiries.convert_hint') }}</p>
            @error('enquiry')
                <p class="mb-3 text-sm text-red-700">{{ $message }}</p>
            @enderror
            <form method="post" action="{{ route($portal.'.enquiries.convert', $enquiry) }}" class="grid gap-3 md:grid-cols-2">
                @csrf
                @if ($enquiry->client_id)
                    <input type="hidden" name="client_id" value="{{ $enquiry->client_id }}">
                    <x-ui.definition :label="__('ui.enquiries.client')">{{ $enquiry->client?->legal_name }}</x-ui.definition>
                @else
                    <x-ui.select id="convert-client" name="client_id" :label="__('ui.enquiries.client')" required :error="$errors->first('client_id')">
                        <option value="">{{ __('ui.enquiries.no_client') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) old('client_id') === (string) $client->id)>{{ $client->legal_name }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                @if ($enquiry->industry_unit_id)
                    <input type="hidden" name="industry_unit_id" value="{{ $enquiry->industry_unit_id }}">
                    <x-ui.definition :label="__('ui.enquiries.unit')">{{ $enquiry->industryUnit?->name }}</x-ui.definition>
                @else
                    <x-ui.select id="convert-unit" name="industry_unit_id" :label="__('ui.enquiries.unit')" required :error="$errors->first('industry_unit_id')">
                        <option value="">{{ __('ui.enquiries.no_unit') }}</option>
                        @foreach ($units as $unit)
                            @continue($enquiry->client_id && $unit->client_id !== $enquiry->client_id)
                            <option value="{{ $unit->id }}" @selected((string) old('industry_unit_id') === (string) $unit->id)>{{ $unit->client?->legal_name }} — {{ $unit->name }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                @if ($enquiry->service_id)
                    <input type="hidden" name="service_id" value="{{ $enquiry->service_id }}">
                    <x-ui.definition :label="__('ui.enquiries.service')">{{ $enquiry->service?->code }} — {{ $enquiry->service?->name }}</x-ui.definition>
                @else
                    <x-ui.select id="convert-service" name="service_id" :label="__('ui.enquiries.service')" required :error="$errors->first('service_id')">
                        <option value="">{{ __('ui.enquiries.no_service') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->code }} — {{ $service->name }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                <x-ui.select id="convert-assignee" name="assigned_to" :label="__('ui.enquiries.assignee')" :error="$errors->first('assigned_to')">
                    <option value="">{{ __('ui.enquiries.unassigned') }}</option>
                    @foreach ($staff as $person)
                        <option value="{{ $person->id }}" @selected((string) old('assigned_to', $enquiry->assigned_to) === (string) $person->id)>{{ $person->name }}</option>
                    @endforeach
                </x-ui.select>
                <div class="md:col-span-2">
                    <x-ui.textarea id="convert-description" name="work_description" :label="__('ui.work_orders.description')" required :error="$errors->first('work_description')">{{ old('work_description', $enquiry->requirement) }}</x-ui.textarea>
                </div>
                <x-ui.input id="convert-start" type="date" name="start_date" :label="__('ui.work_orders.start_date')" :value="old('start_date', today()->format('Y-m-d'))" required :error="$errors->first('start_date')" />
                <x-ui.input id="convert-target" type="date" name="target_completion_date" :label="__('ui.work_orders.target_date')" :value="old('target_completion_date')" :error="$errors->first('target_completion_date')" />
                <div class="md:col-span-2">
                    <x-ui.textarea id="convert-notes" name="internal_notes" :label="__('ui.work_orders.notes')" :error="$errors->first('internal_notes')">{{ old('internal_notes') }}</x-ui.textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-start gap-2 text-sm text-ink">
                        <input type="checkbox" name="confirm_conversion" value="1" class="mt-0.5" @checked(old('confirm_conversion'))>
                        <span>{{ __('ui.enquiries.confirm_conversion') }}</span>
                    </label>
                    @error('confirm_conversion')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <x-ui.button>{{ __('ui.enquiries.convert') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    @endcan
@endsection

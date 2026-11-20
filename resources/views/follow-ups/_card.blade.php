@php
    $portal = auth()->user()->portal->value;
    $returnTo = $returnTo ?? 'parent';
    $parentType = $parent instanceof \App\Models\Enquiry ? 'enquiry' : 'client';
    $subjectName = $parent instanceof \App\Models\Enquiry ? $parent->contact_name : $parent->legal_name;
    $createName = 'follow-up-create-'.$parentType.'-'.$parent->getKey();
    $openCreate = $errors->hasAny(['type', 'scheduled_at', 'notes', 'reminder_at', 'follow_up_status', 'follow_up_assigned_to']) && ! filled(old('follow_up_id'));
    $filterRows = $records->map(fn ($row) => [
        'id' => $row->id,
        'notes' => $row->notes,
        'status' => $row->status->value,
        'type' => $row->type->value,
        'overdue' => $row->isOverdue(),
    ])->values();
@endphp

<div
    x-data="{
        q: '',
        status: '',
        type: '',
        picking: false,
        selected: [],
        toggle(event) {
            const id = event.target.value;
            if (event.target.checked) {
                if (! this.selected.includes(id)) this.selected.push(id);
            } else {
                this.selected = this.selected.filter((value) => value !== id);
            }
        },
        rows: {{ \Illuminate\Support\Js::from($filterRows) }},
        get shown() {
            const q = this.q.trim().toLowerCase();
            return this.rows.filter((row) => {
                if (q && ! String(row.notes).toLowerCase().includes(q)) return false;
                if (this.status === 'overdue' && ! row.overdue) return false;
                if (this.status && this.status !== 'overdue' && row.status !== this.status) return false;
                if (this.type && row.type !== this.type) return false;
                return true;
            }).map((row) => row.id);
        }
    }"
>
    <x-ui.card class="shadow-sm">
        <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
            <h2 class="text-sm font-semibold text-ink sm:me-auto">{{ __('ui.follow_ups.page_title') }}</h2>
            <input x-model="q" type="search" placeholder="{{ __('ui.follow_ups.search') }}" aria-label="{{ __('ui.follow_ups.search') }}" class="h-9 w-full rounded-md border border-line bg-white px-3 text-sm text-ink placeholder:text-muted sm:w-40 lg:w-52">
            <select x-model="status" aria-label="{{ __('ui.follow_ups.status') }}" class="h-9 rounded-md border border-line bg-white px-2 text-sm text-ink">
                <option value="">{{ __('ui.follow_ups.all_statuses') }}</option>
                <option value="overdue">{{ __('ui.follow_ups.overdue') }}</option>
                @foreach (\App\Enums\FollowUpStatus::cases() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
            <select x-model="type" aria-label="{{ __('ui.follow_ups.type') }}" class="h-9 rounded-md border border-line bg-white px-2 text-sm text-ink">
                <option value="">{{ __('ui.follow_ups.all_types') }}</option>
                @foreach (\App\Enums\FollowUpType::cases() as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
            @if (auth()->user()->hasPermission('follow-ups.delete'))
                <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-md border border-line bg-white px-3 text-sm font-medium text-ink hover:bg-forest-50" x-on:click="picking = !picking; selected = []">
                    <x-ui.icon name="check" class="h-4 w-4" x-show="!picking" />
                    <x-ui.icon name="x" class="h-4 w-4" x-show="picking" x-cloak />
                    <span x-show="!picking">{{ __('ui.table.select') }}</span>
                    <span x-show="picking" x-cloak>{{ __('ui.table.selecting') }}</span>
                </button>
                <form x-show="picking" x-cloak method="post" action="{{ route($portal.'.follow-ups.destroy-many') }}" onsubmit="return confirm(@js(__('ui.table.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="confirm_delete" value="1">
                    <input type="hidden" name="return_to" value="parent">
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="ids[]" :value="id">
                    </template>
                    <button type="submit" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-red-700 px-3 text-sm font-medium text-white hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-40" x-bind:disabled="selected.length === 0">
                        <x-ui.icon name="trash" class="h-4 w-4" />
                        {{ __('ui.table.delete_selected') }}
                    </button>
                </form>
            @endif
            @can('create', [App\Models\FollowUp::class, $parent])
                <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-md bg-forest-700 px-3 text-sm font-medium text-white hover:bg-forest-800 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="$dispatch('open-panel', '{{ $createName }}')">
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    {{ __('ui.follow_ups.create') }}
                </button>
            @endcan
        </div>

        @if ($records->isEmpty())
            <p class="px-4 py-3 text-sm text-muted">{{ __('ui.follow_ups.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-canvas text-xs font-medium text-muted">
                        <tr>
                            <th class="px-3 py-2" x-show="picking" x-cloak></th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.table.serial') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_when') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_subject') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.type') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_owner') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.status') }}</th>
                            <th class="px-4 py-2 font-medium">{{ __('ui.follow_ups.col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($records as $followUp)
                            <tr x-show="shown.includes({{ $followUp->id }})" x-cloak>
                                <td class="px-3 py-2.5 align-top" x-show="picking" x-cloak>
                                    <input type="checkbox" value="{{ $followUp->id }}" x-on:change="toggle($event)" class="rounded border-line text-forest-700 focus:ring-forest-700/30" aria-label="{{ $subjectName }}">
                                </td>
                                <td class="px-4 py-2.5 align-top text-muted">{{ $loop->iteration }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top">
                                    <p class="text-ink">{{ $followUp->scheduled_at?->timezone(config('app.timezone'))->format('d-m-Y H:i') }}</p>
                                    @if ($followUp->isOverdue())
                                        <p class="text-xs font-medium text-red-700">{{ __('ui.follow_ups.overdue') }}</p>
                                    @endif
                                </td>
                                <td class="max-w-xs px-4 py-2.5 align-top">
                                    <p class="font-medium text-ink">{{ $subjectName }}</p>
                                    <p class="line-clamp-2 text-xs text-muted">{{ $followUp->notes }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-ink">{{ $followUp->type->label() }}</td>
                                <td class="whitespace-nowrap px-4 py-2.5 align-top text-ink">{{ $followUp->assignee?->name ?: '—' }}</td>
                                <td class="px-4 py-2.5 align-top">
                                    <x-ui.badge :tone="$followUp->status->tone()">{{ $followUp->status->label() }}</x-ui.badge>
                                </td>
                                <td class="px-4 py-2.5 align-top">
                                    <div class="flex items-center gap-1">
                                        @can('complete', $followUp)
                                            @if ($followUp->status === \App\Enums\FollowUpStatus::Pending)
                                                <form method="post" action="{{ route($portal.'.follow-ups.complete', $followUp) }}">
                                                    @csrf
                                                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                                    <button type="submit" title="{{ __('ui.follow_ups.mark_done') }}" aria-label="{{ __('ui.follow_ups.mark_done') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-green-700 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-700">
                                                        <x-ui.icon name="check" class="h-4 w-4" />
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('update', $followUp)
                                            <button type="button" title="{{ __('ui.follow_ups.edit') }}" aria-label="{{ __('ui.follow_ups.edit') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-forest-800 hover:bg-forest-50 focus:outline-none focus:ring-2 focus:ring-forest-700" x-on:click="$dispatch('open-panel', 'follow-up-edit-{{ $followUp->id }}')">
                                                <x-ui.icon name="pencil" class="h-4 w-4" />
                                            </button>
                                        @endcan
                                        @can('delete', $followUp)
                                            <form method="post" action="{{ route($portal.'.follow-ups.destroy', $followUp) }}" onsubmit="return confirm(@js(__('ui.follow_ups.confirm_delete')))">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_to" value="{{ $returnTo }}">
                                                <input type="hidden" name="confirm_delete" value="1">
                                                <button type="submit" title="{{ __('ui.follow_ups.delete') }}" aria-label="{{ __('ui.follow_ups.delete') }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-700">
                                                    <x-ui.icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p x-show="shown.length === 0" x-cloak class="px-4 py-3 text-sm text-muted">{{ __('ui.follow_ups.empty') }}</p>
        @endif
    </x-ui.card>
</div>

@can('create', [App\Models\FollowUp::class, $parent])
    <x-ui.panel :name="$createName" :title="__('ui.follow_ups.create')" variant="drawer" :open="$openCreate">
        <p class="mb-3 text-sm text-muted">{{ __('ui.follow_ups.hint') }}</p>
        @include('follow-ups._form', ['followUp' => null, 'parent' => $parent, 'returnTo' => $returnTo, 'staff' => $staff])
    </x-ui.panel>
@endcan

@foreach ($records as $followUp)
    @can('update', $followUp)
        <x-ui.panel name="follow-up-edit-{{ $followUp->id }}" :title="__('ui.follow_ups.edit')" variant="drawer" :open="(string) old('follow_up_id') === (string) $followUp->id">
            @include('follow-ups._form', ['followUp' => $followUp, 'parent' => $parent, 'returnTo' => $returnTo, 'staff' => $staff])
        </x-ui.panel>
    @endcan
@endforeach

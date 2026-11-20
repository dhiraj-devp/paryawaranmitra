@php
    $portal = auth()->user()->portal->value;
    $editing = $followUp?->exists ?? false;
    $parentType = $parent instanceof \App\Models\Enquiry ? 'enquiry' : 'client';
    $prefix = $editing ? 'follow-up-'.$followUp->id : 'follow-up-new-'.$parentType.'-'.$parent->getKey();
    $scheduled = old('scheduled_at', $followUp?->scheduled_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'));
    $reminder = old('reminder_at', $followUp?->reminder_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'));
    $assigned = old('follow_up_assigned_to', $followUp?->assigned_to ?? ($parent instanceof \App\Models\Enquiry ? $parent->assigned_to : null));
    $status = old('follow_up_status', $followUp?->status?->value ?? \App\Enums\FollowUpStatus::Pending->value);
    $action = $editing
        ? route($portal.'.follow-ups.update', $followUp)
        : route($portal.'.follow-ups.store');
@endphp

<form method="post" action="{{ $action }}" class="grid gap-3">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif
    <input type="hidden" name="followable_type" value="{{ $parentType }}">
    <input type="hidden" name="followable_id" value="{{ $parent->getKey() }}">
    <input type="hidden" name="return_to" value="{{ $returnTo }}">
    @if ($editing)
        <input type="hidden" name="follow_up_id" value="{{ $followUp->id }}">
    @endif
    <x-ui.select :id="$prefix.'-type'" name="type" :label="__('ui.follow_ups.type')" required :error="$errors->first('type')">
        @foreach (\App\Enums\FollowUpType::cases() as $option)
            <option value="{{ $option->value }}" @selected(old('type', $followUp?->type?->value) === $option->value)>{{ $option->label() }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input :id="$prefix.'-when'" type="datetime-local" name="scheduled_at" :label="__('ui.follow_ups.scheduled')" :value="$scheduled" required :error="$errors->first('scheduled_at')" />
    <x-ui.select :id="$prefix.'-staff'" name="follow_up_assigned_to" :label="__('ui.follow_ups.assignee')" :error="$errors->first('follow_up_assigned_to')">
        <option value="">{{ __('ui.follow_ups.unassigned') }}</option>
        @foreach ($staff as $person)
            <option value="{{ $person->id }}" @selected((string) $assigned === (string) $person->id)>{{ $person->name }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.textarea :id="$prefix.'-notes'" name="notes" :label="__('ui.follow_ups.notes')" required :error="$errors->first('notes')">{{ old('notes', $followUp?->notes) }}</x-ui.textarea>
    <x-ui.input :id="$prefix.'-reminder'" type="datetime-local" name="reminder_at" :label="__('ui.follow_ups.reminder')" :value="$reminder" :error="$errors->first('reminder_at')" />
    <x-ui.select :id="$prefix.'-status'" name="follow_up_status" :label="__('ui.follow_ups.status')" required :error="$errors->first('follow_up_status')">
        @foreach (\App\Enums\FollowUpStatus::cases() as $option)
            <option value="{{ $option->value }}" @selected($status === $option->value)>{{ $option->label() }}</option>
        @endforeach
    </x-ui.select>
    <div>
        <x-ui.button>{{ $editing ? __('ui.follow_ups.edit') : __('ui.follow_ups.create') }}</x-ui.button>
    </div>
</form>

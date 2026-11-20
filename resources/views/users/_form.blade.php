@php
    $portal = auth()->user()->portal->value;
    $editing = $account->exists;
@endphp

<form method="post" action="{{ $editing ? route($portal.'.users.update', $account) : route($portal.'.users.store') }}" class="grid max-w-xl gap-4">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <x-ui.input id="user-name" name="name" :label="__('ui.users.name')" :value="old('name', $account->name)" required :error="$errors->first('name')" />
    <x-ui.input id="user-email" type="email" name="email" :label="__('ui.users.email')" :value="old('email', $account->email)" required :error="$errors->first('email')" />

    <x-ui.select id="user-portal" name="portal" :label="__('ui.users.portal')" required :error="$errors->first('portal')">
        @foreach (\App\Enums\Portal::cases() as $option)
            <option value="{{ $option->value }}" @selected(old('portal', $account->portal?->value) === $option->value)>{{ $option->label() }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.select id="user-role" name="role_id" :label="__('ui.users.role')" required :error="$errors->first('role_id')">
        <option value="">{{ __('ui.users.choose_role') }}</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected((string) old('role_id', $account->roles->first()?->id) === (string) $role->id)>{{ $role->portal->label() }} — {{ $role->label() }}</option>
        @endforeach
    </x-ui.select>

    <x-ui.input id="user-password" type="password" name="password" :label="__('ui.users.password')" autocomplete="new-password" :required="! $editing" :error="$errors->first('password')" />
    <x-ui.input id="user-password-confirmation" type="password" name="password_confirmation" :label="__('ui.users.password_confirmation')" autocomplete="new-password" />
    @if ($editing)
        <p class="-mt-2 text-sm text-muted">{{ __('ui.users.password_hint') }}</p>
    @endif

    <label class="flex items-center gap-2 text-sm text-ink">
        <input type="checkbox" name="is_active" value="1" class="rounded border-line text-forest-700 focus:ring-forest-700/30" @checked(old('is_active', $account->is_active))>
        {{ __('ui.users.active_label') }}
    </label>
    @error('is_active')
        <p class="text-sm text-red-700">{{ $message }}</p>
    @enderror

    <div class="flex items-center gap-3">
        <x-ui.button>{{ __('ui.users.save') }}</x-ui.button>
        <a href="{{ route($portal.'.users.index') }}" class="text-sm text-muted hover:underline">{{ __('ui.enquiries.cancel') }}</a>
    </div>
</form>

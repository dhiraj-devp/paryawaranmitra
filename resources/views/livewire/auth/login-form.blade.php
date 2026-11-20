<form wire:submit="authenticate" class="space-y-4">
    <x-ui.input id="email-{{ $portal }}" :label="__('ui.auth.email')" type="email" wire:model="email" autocomplete="username" required autofocus :error="$errors->first('email')" />
    <x-ui.input id="password-{{ $portal }}" :label="__('ui.auth.password')" type="password" wire:model="password" autocomplete="current-password" required :error="$errors->first('password')" />

    <label class="flex items-center gap-2 text-sm text-ink">
        <input type="checkbox" wire:model="remember" class="rounded border-line text-forest-700 focus:ring-forest-700/30">
        <span>{{ __('ui.auth.remember') }}</span>
    </label>

    <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
        {{ __('ui.auth.sign_in') }}
    </x-ui.button>

    <noscript>
        <p class="text-sm text-muted">{{ __('ui.auth.javascript_required') }}</p>
    </noscript>
</form>

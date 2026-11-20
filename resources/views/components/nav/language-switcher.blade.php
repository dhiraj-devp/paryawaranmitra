@props(['context' => 'app'])

<form method="post" action="{{ route('locale.update') }}" class="flex items-center gap-2">
    @csrf
    <label for="locale-{{ $context }}" class="sr-only">{{ __('ui.header.language') }}</label>
    <select id="locale-{{ $context }}" name="locale" onchange="this.form.submit()" class="rounded-md border border-line bg-white px-2 py-1.5 text-sm text-ink focus:border-forest-700 focus:outline-none focus:ring-2 focus:ring-forest-700/20">
        @foreach (config('paryavaran.locales') as $code => $meta)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $meta['native'] }}</option>
        @endforeach
    </select>
    <noscript>
        <button type="submit" class="rounded-md border border-line bg-white px-2 py-1.5 text-sm">{{ __('ui.header.language_apply') }}</button>
    </noscript>
</form>

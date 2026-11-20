<div class="overflow-x-auto">
    <table class="w-full min-w-full border-collapse text-left text-sm">
        @isset($head)
            <thead class="border-b border-line text-xs font-medium uppercase tracking-wide text-muted">
                <tr>{{ $head }}</tr>
            </thead>
        @endisset
        <tbody class="divide-y divide-line">
            {{ $slot }}
        </tbody>
    </table>
</div>

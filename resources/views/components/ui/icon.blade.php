@props(['name'])

@php
    $paths = [
        'home' => 'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z',
        'bell' => 'M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9Zm6 12a2.5 2.5 0 0 0 2.4-2h-4.8A2.5 2.5 0 0 0 12 21Z',
        'inbox' => 'M3 13h5l2 3h4l2-3h5v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6Zm0-2 3-7h12l3 7',
        'users' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 1a2.5 2.5 0 1 0 0-5M3 19a6 6 0 0 1 12 0M14 19a5 5 0 0 1 7 0',
        'clipboard' => 'M9 4h6a2 2 0 0 1 2 2h1a1 1 0 0 1 1 1v13H5V7a1 1 0 0 1 1-1h1a2 2 0 0 1 2-2Zm0 0a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2',
        'layers' => 'm12 3 9 5-9 5L3 8l9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5',
        'banknotes' => 'M3 7h18v10H3V7Zm2 2v6h14V9M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
        'folder' => 'M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z',
        'file' => 'M7 3h7l5 5v13H7V3Zm7 0v5h5M9 13h6M9 17h6',
        'megaphone' => 'M4 10v4h3l8 4V6L7 10H4Zm14 0a4 4 0 0 1 0 4M8 14l1 5h2',
        'document' => 'M7 3h8l4 4v14H7V3Zm8 0v4h4M9 12h6M9 16h4',
        'receipt' => 'M6 3h12v18l-2-1-2 1-2-1-2 1-2-1-2 1V3Zm3 5h6M9 11h6M9 15h4',
        'card' => 'M3 7h18v10H3V7Zm0 3h18M7 14h4',
        'wallet' => 'M4 7h14a2 2 0 0 1 2 2v8H6a2 2 0 0 1-2-2V7Zm0 0V6a2 2 0 0 1 2-2h11M16 13h2',
        'chart' => 'M4 19V5M4 19h16M8 16v-4M12 16V8M16 16v-6',
        'shield' => 'M12 3 5 6v6c0 4.5 3 7 7 9 4-2 7-4.5 7-9V6l-7-3Z',
        'stamp' => 'M8 11V7a4 4 0 0 1 8 0v4M6 14h12v3H6v-3Zm-2 3h16v3H4v-3Z',
        'landmark' => 'M4 10h16M6 10v8M10 10v8M14 10v8M18 10v8M3 18h18M12 3 3 8h18L12 3Z',
        'droplet' => 'M12 3s6 6.2 6 10a6 6 0 0 1-12 0c0-3.8 6-10 6-10Z',
        'pin' => 'M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Zm0-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
        'check' => 'M5 12.5 9.2 17 19 7',
        'chat' => 'M5 6h14v9H8l-3 3V6Z',
        'presentation' => 'M4 5h16v10H4V5Zm8 10v4M8 19h8',
        'bolt' => 'M13 3 5 14h6l-1 7 8-11h-6l1-7Z',
        'key' => 'M8 15a5 5 0 1 1 4.3-7.5L21 8v3h-3v2h-3l-2.2-2.1A5 5 0 0 1 8 15Zm0-2.5h.01',
        'settings' => 'M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z M4 12h2M18 12h2M12 4v2M12 18v2M6.2 6.2l1.4 1.4M16.4 16.4l1.4 1.4M17.8 6.2l-1.4 1.4M7.6 16.4l-1.4 1.4',
        'search' => 'm20 20-4.2-4.2M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z',
        'plus' => 'M12 5v14M5 12h14',
        'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 20a8 8 0 0 1 16 0',
        'chevron-left' => 'M15 6 9 12l6 6',
        'pencil' => 'M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z',
        'trash' => 'M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'x' => 'M6 6l12 12M18 6 6 18',
        'logout' => 'M10 7V5H5v14h5v-2M10 12h9M16 9l3 3-3 3',
    ];
    $path = $paths[$name] ?? 'M12 12h.01';
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'aria-hidden' => 'true']) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $path }}" />
</svg>

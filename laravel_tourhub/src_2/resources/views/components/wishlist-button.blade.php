@props([
    'item' => [],
    'log' => null,
    'compact' => false,
    'class' => '',
])

@php
    $itemArray = is_array($item) ? $item : [];

    $activeLogId = $log?->id ?? null;

    if (! $activeLogId && request()->integer('log') > 0) {
        $activeLogId = request()->integer('log');
    }

    $payload = \App\Models\Wishlist::normalizeDestinationSnapshot($itemArray);

    $wished = auth()->check()
        && \App\Models\Wishlist::isWishedByUser((int) auth()->id(), $itemArray);

    $jsonPayload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?: '{}';

    $baseClass = 'inline-flex items-center justify-center gap-2 rounded-2xl font-black transition shadow-sm';

    $sizeClass = $compact
        ? 'px-3 py-2 text-xs'
        : 'px-4 py-3 text-sm';

    $stateClass = $wished
        ? 'bg-amber-400 text-slate-950 hover:bg-amber-500 shadow-amber-500/20'
        : 'bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-amber-50 hover:text-amber-700 hover:ring-amber-200';
@endphp

<form method="POST" action="{{ route('wishlist.toggle') }}" class="{{ $class }}">
    @csrf

    @if ($activeLogId)
        <input type="hidden" name="recommendation_log_id" value="{{ $activeLogId }}">
    @endif

    <input
        type="hidden"
        name="destination_payload"
        value="{{ e($jsonPayload) }}"
    >

    <button
        type="submit"
        class="{{ $baseClass }} {{ $sizeClass }} {{ $stateClass }}"
        title="{{ $wished ? 'Hapus dari wishlist' : 'Tambahkan ke wishlist' }}"
    >
        <span>{{ $wished ? '★' : '☆' }}</span>
        <span>{{ $wished ? 'Tersimpan' : 'Wishlist' }}</span>
    </button>
</form>

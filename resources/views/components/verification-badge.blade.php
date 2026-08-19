@props(['status'])

@php
    $statusValue = is_object($status) ? $status->value : (string) $status;
    
    $configs = [
        'MENUNGGU_VERIFIKASI' => [
            'label' => 'Menunggu Verifikasi',
            'bg' => 'bg-warning-light text-warning border-warning/30',
            'dot' => 'bg-warning',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        ],
        'TERVERIFIKASI' => [
            'label' => 'Terverifikasi',
            'bg' => 'bg-success-light text-success border-success/30',
            'dot' => 'bg-success',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        ],
        'DITOLAK' => [
            'label' => 'Ditolak',
            'bg' => 'bg-danger-light text-danger border-danger/30',
            'dot' => 'bg-danger',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        ],
    ];

    $cfg = $configs[$statusValue] ?? [
        'label' => $statusValue,
        'bg' => 'bg-background text-text-secondary border-border',
        'dot' => 'bg-text-secondary',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm text-xs font-semibold border ' . $cfg['bg']]) }}>
    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        {!! $cfg['icon'] !!}
    </svg>
    <span>{{ $cfg['label'] }}</span>
</span>

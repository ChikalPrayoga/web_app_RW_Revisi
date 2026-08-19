@props(['status'])

@php
    $statusValue = is_object($status) ? $status->value : (string) $status;

    $configs = [
        'TETAP' => [
            'label' => 'Warga Tetap',
            'bg' => 'bg-primary-light text-primary border-primary/20',
        ],
        'KONTRAK' => [
            'label' => 'Warga Kontrak',
            'bg' => 'bg-secondary-light text-secondary border-secondary/20',
        ],
        'PINDAH' => [
            'label' => 'Pindah',
            'bg' => 'bg-background text-text-secondary border-border',
        ],
        'MENINGGAL' => [
            'label' => 'Meninggal',
            'bg' => 'bg-danger-light text-danger border-danger/20',
        ],
    ];

    $cfg = $configs[$statusValue] ?? [
        'label' => $statusValue,
        'bg' => 'bg-background text-text-secondary border-border',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-sm text-xs font-medium border ' . $cfg['bg']]) }}>
    {{ $cfg['label'] }}
</span>

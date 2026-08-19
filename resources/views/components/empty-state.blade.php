@props(['title', 'description' => null, 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'bg-surface p-12 rounded-md border border-border text-center flex flex-col items-center justify-center']) }}>
    <div class="w-16 h-16 rounded-full bg-primary-light text-primary flex items-center justify-center mb-4">
        {{ $icon ?? '' }}
        @if(!isset($icon))
            <svg class="w-8 h-8 text-primary/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        @endif
    </div>
    <h3 class="text-base font-semibold text-text-primary font-display mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-text-secondary max-w-sm mb-6">{{ $description }}</p>
    @endif
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-sm transition-colors min-h-touch min-w-touch">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>{{ $actionLabel }}</span>
        </a>
    @endif
    {{ $slot }}
</div>

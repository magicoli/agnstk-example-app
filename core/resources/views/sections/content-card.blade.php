{{-- Card-style content section --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            @if(isset($icon))<i class="{{ $icon }}"></i> @endif
            {{ $title ?? 'Content' }}
        </h3>
    </div>
    <div class="card-body">
        {!! $content !!}
    </div>
</div>

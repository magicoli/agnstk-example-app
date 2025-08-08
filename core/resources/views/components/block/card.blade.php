{{-- Card wrapper for content sections --}}
<div class="card">
    @if(isset($title))
    <div class="card-header">
        <h4 class="card-title mb-0">
            @if(isset($icon))<i class="{{ $icon }}"></i> @endif
            {{ $title }}
        </h4>
    </div>
    @endif
    <div class="card-body">
        {!! $content !!}
    </div>
</div>

{{-- Generic block component for services --}}
@php
    $cssClasses = 'service-block';
    if (isset($attributes['class'])) {
        $cssClasses .= ' ' . $attributes['class'];
    }
    $otherAttributes = $attributes ?? [];
    unset($otherAttributes['class']); // Remove class from other attributes
    $attributeString = '';
    foreach ($otherAttributes as $key => $value) {
        $attributeString .= ' ' . e($key) . '="' . e($value) . '"';
    }
@endphp
<div class="{{ $cssClasses }}"{{ $attributeString }}>
    @if(isset($title) && !empty($title))
    <h4 class="block-title">
        @if(isset($icon))<i class="{{ $icon }}"></i> @endif
        {{ $title }}
    </h4>
    @endif
    
    @if(isset($content))
    <p class="lead">
        {!! $content !!}
    </p>
    @endif
    
    @if(isset($platform) || isset($timestamp))
    <small class="text-muted">
        @if(isset($platform)){{ $platform }}@endif
        @if(isset($platform) && isset($timestamp)) • @endif
        @if(isset($timestamp)){{ $timestamp }}@endif
    </small>
    @endif

</div>

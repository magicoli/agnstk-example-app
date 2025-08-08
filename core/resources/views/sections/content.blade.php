    <h2>Using shortcode directive:</h2>
    {{-- @shortcode('hello', ['title' => 'Directive Title', 'content' => 'Content from directive']) --}}
    
    <h2>Using helper function:</h2>
    {{-- {{ do_shortcode('hello', ['title' => 'Helper Title', 'content' => 'Content from helper']) }} --}}
    
    <h2>Using service-specific helper:</h2>
    {{-- {{ hello(['title' => 'Service Helper Title', 'content' => 'Content from service helper']) }} --}}

{{-- Basic content section - just outputs content as-is --}}
{!! $content !!}


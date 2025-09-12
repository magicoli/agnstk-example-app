@php
    $errorCode = view()->yieldContent('code') ?? '500';
    $errorTitle = view()->yieldContent('title') ?: __('Server Error');
    $errorMessage = view()->yieldContent('message') ?: __('An unexpected error occurred.');

    // Add detailed exception in debug mode using Laravel's preferred method
    if(config('app.debug') && isset($exception)) {
        $errorMessage .= sprintf(
            '<hr><details><summary>%s</summary><pre>%s</pre></details>',
            __('Debug Information'),
            htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8')
        );
    }
    
    $errorContent = sprintf(
        '<h1>%s %s</h1><p>%s</p>',
        $errorCode,
        $errorTitle,
        $errorMessage
    );
@endphp

{!! view('page', ['content' => $errorContent]) !!}

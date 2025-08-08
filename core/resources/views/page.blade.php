@extends('layouts.html')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if(isset($title) && $title)
                <h1>{{ $title }}</h1>
            @endif
            {!! $content !!}
        </div>
    </div>
</div>
@endsection

<div class="card">
    <div class="card-header"> {{ __('Dashboard') }}
    </div>

    <div class="card-body">
        @if (session('status'))
            <div class="alert alert-success" role="alert"> {{ session('status') }}
            </div>
        @endif

        <p>{{ __('Welcome to your dashboard,') }} {{ Auth::user()->name }}!</p>
        <p>{{ __('You are logged in and can access protected content.') }}</p>
    </div>
</div>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AGNSTK (debug, fallback from app.blade.php)') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Built Assets -->
    <link rel="stylesheet" href="{{ build_asset('main-styles.css') }}">
    <script src="{{ build_asset('main-scripts.js') }}" defer></script>
    <script src="{{ asset('js/prism.js') }}" defer></script>
</head>
<body>
    <div id="app" class="flex-grow-1 d-flex flex-column min-vh-100">
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    @if(config('app.logo'))
                        <img src="{{ asset(config('app.logo')) }}" alt="{{ config('app.name', 'AGNSTK') }} Logo" style="height: 32px;" class="me-2">
                    @endif
                    {{ config('app.name', 'AGNSTK (debug, fallback from app.blade.php)') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @php
                            $menuItems = \Agnstk\Services\PageService::getMenuItems();
                        @endphp
                        @foreach($menuItems as $item)
                            {{-- @if(!($item['auth_required'] ?? false) || auth()->check()) --}}
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                                </li>
                            {{-- @endif --}}
                        @endforeach
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre> {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('dashboard') }}"> {{ __('Dashboard') }}
                                    </a>
                                    
                                    <div class="dropdown-divider"></div>
                                    
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4 flex-grow-1">
            @yield('content')
        </main>

        <footer class="bg-white text-center text-lg-start">
            <div class="text-center p-3">
                © {{ date('Y') }} {{ config('app.name', 'AGNSTK (debug, fallback from app.blade.php)') }} {{ config('app.version') }}. All rights reserved.
            </div>
        </footer>
    </div>
    </body>
</html>

# AGNSTK Coding Standards & Architecture

## Development Environment
- Test server: `composer run dev` → http://localhost:8000
- Laravel 12 with Pest testing framework
- Use Artisan commands for all component generation: `php artisan make:*`

## CRITICAL: Architecture Overview
- **Laravel-First**: Use Laravel's built-in features (routing, auth, templating, etc.) - never recreate what Laravel provides
- **Project Root**: Laravel application root serves as the base for ALL implementations
- **Core Logic**: Business logic in `app/Services/` using Laravel patterns
- **CMS Adapters**: Future adapters will consume Laravel services/APIs (currently disabled)

## Laravel Standards (Strictly Follow)
- **File Naming**: PascalCase for classes (Laravel standard): `MembershipService.php`, `BlockController.php`
- **PSR-4 Namespacing**: `App\Services\BlockService` → `app/Services/BlockService.php`
- **Artisan Generation**: Always use `php artisan make:controller`, `php artisan make:model`, etc.
- **Blade Templates**: Use Laravel's templating system, extend layouts properly
- **Routes**: Define in `routes/web.php` using Laravel routing
- **Database**: Use Eloquent models and migrations

## URL Generation (CRITICAL - Always Follow)
**NEVER hardcode URLs or use environment-specific logic for URL generation.**

Always use the global helper functions that work in ALL environments:

### PHP/Blade Templates:
- **`base_url($path)`** - For application URLs: `base_url('about')` → `https://agnstk-example-app.org/agnstk-example-app/about`
- **`public_url($path)`** - For public assets: `public_url('js/prism.js')` → `https://agnstk-example-app.org/agnstk-example-app/public/js/prism.js`
- **`build_asset($filename)`** - For built assets: `build_asset('main-styles.css')` → `https://agnstk-example-app.org/agnstk-example-app/public/build/assets/main-styles.css`
- **`asset($path)`** - Laravel's helper for public assets

### Examples in Blade:
```blade
<!-- Navigation links -->
<a href="{{ base_url('about') }}">About</a>
<a href="{{ base_url('/developers') }}">Developers</a>

<!-- Assets -->
<script src="{{ asset('js/prism.js') }}"></script>
<script src="{{ public_url('js/custom.js') }}"></script>
<link rel="stylesheet" href="{{ build_asset('main-styles.css') }}">

<!-- Images -->
<img src="{{ asset('images/logo.png') }}" alt="Logo">
<img src="{{ public_url('images/banner.jpg') }}" alt="Banner">
```

### Twig (Future CMS Integration):
- Use corresponding Twig functions: `{{ base_url('path') }}`, `{{ public_url('path') }}`, `{{ asset('path') }}`

## Development Principles
- **Laravel Documentation First**: Always check Laravel docs before writing custom code
- **Scalable Design**: Shared logic in services, avoid code duplication
- **Framework Integration**: Leverage Laravel's IoC container, facades, and service providers
- **Testing**: Use Pest for readable, maintainable tests
- **Universal URL Handling**: Use helper functions that work in development AND production

## Current Focus: Standalone Laravel App
- Authentication & registration working via Laravel Breeze/UI
- Block system implementation using Laravel patterns
- Page management through Laravel controllers/models
- API endpoints using Laravel API resources

## Future CMS Integration (Disabled)
- WordPress, Drupal, October CMS adapters will consume Laravel services
- Each adapter will have separate composer.json for CMS-specific dependencies
- Root directory structure allows multi-platform compatibility

## For Automated Agents
- **ALWAYS** check Laravel documentation before implementing features
- **NEVER** recreate what Laravel already provides (auth, routing, templating, etc.)
- **USE** Artisan commands for generating boilerplate code
- **FOLLOW** Laravel conventions and naming standards strictly
- **LEVERAGE** Laravel's service container and dependency injection
- **ALWAYS** use `base_url()`, `public_url()`, `build_asset()`, or `asset()` for URL generation
- **NEVER** hardcode URLs or create environment-specific URL logic

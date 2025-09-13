<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Agnstk\Http\Controllers\PageController;
use Agnstk\Http\Controllers\Auth\LoginController;
use Agnstk\Http\Controllers\Auth\RegisterController;
use Agnstk\Http\Controllers\Auth\ForgotPasswordController;
use Agnstk\Http\Controllers\Auth\ResetPasswordController;
use Agnstk\Http\Controllers\Auth\ConfirmPasswordController;
use Agnstk\Http\Controllers\Auth\VerificationController;
use Agnstk\Services\PageService;

// Home page
Route::get('/', [PageController::class, 'home'])->name('home');

// Authentication Routes
// Login Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Registration Routes
Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

// Password Reset Routes
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

// Email Verification Routes
Route::get('email/verify', [VerificationController::class, 'show'])->name('verification.notice');
Route::get('email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
Route::post('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

// Password Confirmation Routes
Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);

// Dynamic page routes based on configuration
$pages = PageService::getEnabledPages();
foreach ($pages as $slug => $page) {
    $uri = $page['uri'];
    $routeName = $slug;
    
    // Handle authentication requirement
    if ($page['auth_required'] ?? false) {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->middleware('auth')
            ->defaults('slug', $slug);
    } else {
        Route::get($uri, [PageController::class, 'show'])
            ->name($routeName)
            ->defaults('slug', $slug);
    }
}

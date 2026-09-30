<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app', ['page' => 'display'])->name('display');

Route::middleware('guest')->group(function (): void {
    Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
});

Route::middleware(['auth', EnsureUserIsAdmin::class])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/live-hosts', [AdminDashboardController::class, 'liveHostBoard'])->name('admin.live-hosts');
    foreach (['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'] as $resource) {
        Route::get('/admin/'.$resource.'/create', [AdminDashboardController::class, 'create'])->defaults('resource', $resource)->name('admin.'.$resource.'.create');
        Route::get('/admin/'.$resource.'/{id}/edit', [AdminDashboardController::class, 'edit'])->whereNumber('id')->defaults('resource', $resource)->name('admin.'.$resource.'.edit');
    }
    Route::get('/admin/{section}', [AdminDashboardController::class, 'section'])
        ->whereIn('section', ['promotions', 'achievements', 'live-hosts', 'birthdays', 'hosts', 'channels', 'weekly-meetings', 'display-preview'])
        ->name('admin.section');
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

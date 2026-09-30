<?php

use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminLiveHostBoardController;
use App\Http\Controllers\DisplayController;
use Illuminate\Support\Facades\Route;

Route::get('/display', DisplayController::class)->name('api.display');

Route::prefix('admin')->middleware(['web', 'auth', 'role:admin'])->group(function (): void {
    Route::get('/', fn () => response()->json(['success' => true, 'data' => ['authenticated' => true]]))->name('api.admin.index');
    Route::get('/dashboard', [AdminContentController::class, 'dashboard'])->name('api.admin.dashboard');
    Route::get('/live-channels', [AdminContentController::class, 'channels'])->name('api.admin.live-channels');
    Route::get('/live-hosts/board', [AdminLiveHostBoardController::class, 'show'])->name('api.admin.live-hosts.board.show');
    Route::put('/live-hosts/board', [AdminLiveHostBoardController::class, 'update'])->name('api.admin.live-hosts.board.update');
    Route::get('/{resource}', [AdminContentController::class, 'index'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings']);
    Route::post('/{resource}', [AdminContentController::class, 'store'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings']);
    Route::get('/{resource}/{id}', [AdminContentController::class, 'show'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'])->whereNumber('id');
    Route::put('/{resource}/{id}', [AdminContentController::class, 'update'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'])->whereNumber('id');
    Route::patch('/{resource}/{id}/toggle', [AdminContentController::class, 'toggle'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'])->whereNumber('id');
    Route::delete('/{resource}/{id}', [AdminContentController::class, 'destroy'])->whereIn('resource', ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'])->whereNumber('id');
});

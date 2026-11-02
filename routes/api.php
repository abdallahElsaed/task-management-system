<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:register')
    ->name('auth.register');
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

    // One read that replaces six list calls, so it sits beside the modules it
    // summarises rather than inside either of them.
    Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard.show');

    Route::prefix('projects')->name('projects.')->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('/{project}', [ProjectController::class, 'show'])
            ->whereNumber('project')
            ->name('show');
        Route::match(['put', 'patch'], '/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->name('update');
        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->whereNumber('project')
            ->name('destroy');
    });

    // The list spans every project the caller owns, with the project as an
    // optional filter rather than a path segment.
    Route::prefix('tasks')->name('tasks.')->group(function (): void {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/{task}', [TaskController::class, 'show'])
            ->whereNumber('task')
            ->name('show');
        Route::match(['put', 'patch'], '/{task}', [TaskController::class, 'update'])
            ->whereNumber('task')
            ->name('update');
        Route::delete('/{task}', [TaskController::class, 'destroy'])
            ->whereNumber('task')
            ->name('destroy');
    });
});

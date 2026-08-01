<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->name('auth.register');
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');

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
});

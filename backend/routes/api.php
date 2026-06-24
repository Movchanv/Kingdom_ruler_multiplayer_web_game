<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RealtimeDemoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('auth.register');
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    Route::post('auth/forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::post('auth/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.reset');

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('account/export', [AccountController::class, 'export'])->name('account.export');
        Route::get('account/consent', [AccountController::class, 'consent'])->name('account.consent');
        Route::post('account/consent', [AccountController::class, 'acceptConsent'])->name('account.consent.accept');
        Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');

        Route::middleware('verified')->group(function (): void {
            Route::get('game/countries', [GameController::class, 'countries'])->name('game.countries');
            Route::post('game/join', [GameController::class, 'join'])->name('game.join');
            Route::post('game/towns/{town}/enter', [GameController::class, 'enterTown'])->name('game.towns.enter');
            Route::get('game/state', [GameController::class, 'state'])->name('game.state');
            Route::post('game/actions', [GameController::class, 'performAction'])->name('game.actions.perform');
            Route::post('realtime/announce', [RealtimeDemoController::class, 'broadcast'])->name('realtime.announce');
        });
    });
});

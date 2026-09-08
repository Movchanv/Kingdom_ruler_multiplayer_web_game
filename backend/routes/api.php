<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminEventController;
use App\Http\Controllers\Api\AdminLawController;
use App\Http\Controllers\Api\AdminVoteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RealtimeDemoController;
use App\Http\Controllers\Api\ResearchController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\VoteController;
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

        Route::post('support/reports', [SupportController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('support.reports.store');

        Route::get('account/export', [AccountController::class, 'export'])->name('account.export');
        Route::get('account/consent', [AccountController::class, 'consent'])->name('account.consent');
        Route::post('account/consent', [AccountController::class, 'acceptConsent'])->name('account.consent.accept');
        Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');

        Route::middleware('verified')->group(function (): void {
            Route::get('game/countries', [GameController::class, 'countries'])->name('game.countries');
            Route::post('game/join', [GameController::class, 'join'])->name('game.join');
            Route::get('game/towns', [GameController::class, 'towns'])->name('game.towns.index');
            Route::post('game/towns/{town}/enter', [GameController::class, 'enterTown'])->name('game.towns.enter');
            Route::get('game/state', [GameController::class, 'state'])->name('game.state');
            Route::get('game/seasons/last', [GameController::class, 'lastSeason'])->name('game.seasons.last');
            Route::get('game/seasons/{game}/results', [GameController::class, 'seasonResults'])->name('game.seasons.results');
            Route::post('game/actions', [GameController::class, 'performAction'])->name('game.actions.perform');
            Route::post('game/buildings/{townBuilding}/build', [GameController::class, 'build'])->name('game.buildings.build');
            Route::post('game/adventure', [GameController::class, 'adventure'])->name('game.adventure');
            Route::post('realtime/announce', [RealtimeDemoController::class, 'broadcast'])->name('realtime.announce');

            Route::get('game/votes/current', [VoteController::class, 'current'])->name('game.votes.current');
            Route::post('game/votes/{vote}/ballot', [VoteController::class, 'ballot'])->name('game.votes.ballot');

            Route::get('game/chat', [ChatController::class, 'index'])->name('game.chat.index');
            Route::post('game/chat', [ChatController::class, 'store'])->name('game.chat.store');

            Route::middleware('admin')->group(function (): void {
                Route::get('admin/overview', [AdminController::class, 'overview'])->name('admin.overview');
                Route::get('admin/monitoring', [MonitoringController::class, 'index'])->name('admin.monitoring');
                Route::get('admin/bug-reports', [SupportController::class, 'index'])->name('admin.bug-reports');
                Route::get('admin/games', [AdminController::class, 'games'])->name('admin.games');
                Route::get('admin/laws', [AdminLawController::class, 'index'])->name('admin.laws.index');
                Route::post('admin/laws', [AdminLawController::class, 'store'])->name('admin.laws.store');
                Route::get('admin/users', [AdminController::class, 'users'])->name('admin.users');
                Route::post('admin/users/{user}/ban', [AdminController::class, 'banUser'])->name('admin.users.ban');
                Route::post('admin/users/{user}/unban', [AdminController::class, 'unbanUser'])->name('admin.users.unban');
                Route::post('admin/games/{game}/end', [AdminController::class, 'endSeason'])->name('admin.games.end');
                Route::get('admin/events', [AdminEventController::class, 'index'])->name('admin.events.index');
                Route::post('admin/events', [AdminEventController::class, 'store'])->name('admin.events.store');
                Route::post('admin/events/{event}/trigger', [AdminEventController::class, 'trigger'])->name('admin.events.trigger');
                Route::post('admin/votes', [AdminVoteController::class, 'open'])->name('admin.votes.open');
            });

            Route::middleware('researcher')->group(function (): void {
                Route::get('research/overview', [ResearchController::class, 'overview'])->name('research.overview');
                Route::get('research/actions', [ResearchController::class, 'actions'])->name('research.actions');
                Route::get('research/seasons', [ResearchController::class, 'seasons'])->name('research.seasons');

                Route::get('research/exports/actions', [ResearchController::class, 'exportActions'])->name('research.exports.actions');
                Route::get('research/exports/seasons', [ResearchController::class, 'exportSeasons'])->name('research.exports.seasons');
            });
        });
    });
});

<?php

namespace App\Providers;

use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\LocallyFundedProject;
use App\Models\Ticket;
use App\Models\User;
use App\Models\ActivityLog;
use App\Observers\LocallyFundedProjectObserver;
use App\Services\ActivityLogService;
use App\Services\FundUtilizationWorkflowService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LocallyFundedProject::observe(LocallyFundedProjectObserver::class);

        Event::listen(Login::class, function (Login $event): void {
            app(ActivityLogService::class)->log(
                $event->user,
                ActivityLog::ACTION_LOGIN,
                'User logged in successfully.',
                [
                    'request' => request(),
                    'properties' => [
                        'guard' => $event->guard,
                    ],
                ],
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            app(ActivityLogService::class)->log(
                $event->user,
                ActivityLog::ACTION_LOGOUT,
                'User logged out.',
                [
                    'request' => request(),
                    'properties' => [
                        'guard' => $event->guard,
                    ],
                ],
            );
        });

        Event::listen(Registered::class, function (Registered $event): void {
            app(ActivityLogService::class)->log(
                $event->user,
                ActivityLog::ACTION_REGISTER,
                'User registration completed.',
                [
                    'request' => request(),
                    'properties' => [
                        'status' => $event->user?->status,
                        'role' => $event->user?->role,
                    ],
                ],
            );
        });
        // Ticketing authorization is registered in ModularServiceProvider via TicketPolicy

        Gate::define('fund-utilization.validateWorkflow', function ($user, FundUtilizationApprovalWorkflow $workflow): bool {
            if (!$user instanceof User) {
                return false;
            }
            
            return app(FundUtilizationWorkflowService::class)->canActorValidate($workflow, $user);
        });
    }
}

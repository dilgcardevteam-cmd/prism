<?php

namespace App\Providers;

use App\Models\LocallyFundedProject;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Observers\LocallyFundedProjectObserver;
use App\Services\ActivityLogService;
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

        Gate::define('ticketing.submit', fn ($user) => $user->isLguUser());

        Gate::define('ticketing.view', function ($user, Ticket $ticket): bool {
            $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable()
                : $user->normalizedRegion();
            $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable($ticket->region_scope)
                : strtolower(trim((string) $ticket->region_scope));

            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->isLguUser()) {
                return (int) $ticket->submitted_by === (int) $user->getKey();
            }

            if ($user->isProvincialUser()) {
                return $user->normalizedProvince() !== ''
                    && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince();
            }

            if ($user->isRegionalUser()) {
                return $userRegionComparable !== ''
                    && $ticketRegionComparable === $userRegionComparable
                    && in_array($ticket->status, [
                        Ticket::STATUS_ESCALATED_TO_REGION,
                        Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                        Ticket::STATUS_RESOLVED_BY_REGION,
                        Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                        Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                        Ticket::STATUS_CLOSED,
                    ], true);
            }

            return false;
        });

        Gate::define('ticketing.acceptProvince', function ($user, Ticket $ticket): bool {
            return $user->isProvincialUser()
                && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince()
                && $ticket->current_level === Ticket::LEVEL_PROVINCIAL
                && $ticket->status === Ticket::STATUS_SUBMITTED
                && $ticket->assigned_to === null;
        });

        Gate::define('ticketing.acceptRegion', function ($user, Ticket $ticket): bool {
            $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable()
                : $user->normalizedRegion();
            $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable($ticket->region_scope)
                : strtolower(trim((string) $ticket->region_scope));

            return $user->isRegionalUser()
                && $ticketRegionComparable === $userRegionComparable
                && $ticket->current_level === Ticket::LEVEL_REGIONAL
                && $ticket->status === Ticket::STATUS_ESCALATED_TO_REGION
                && $ticket->assigned_to === null;
        });

        Gate::define('ticketing.addComment', function ($user, Ticket $ticket): bool {
            if ($user->isProvincialUser() && $ticket->current_level === Ticket::LEVEL_PROVINCIAL) {
                return Gate::forUser($user)->allows('ticketing.manageProvince', $ticket);
            }

            if ($user->isRegionalUser() && $ticket->current_level === Ticket::LEVEL_REGIONAL) {
                return Gate::forUser($user)->allows('ticketing.manageRegion', $ticket);
            }

            return Gate::forUser($user)->allows('ticketing.view', $ticket);
        });

        Gate::define('ticketing.manageProvince', function ($user, Ticket $ticket): bool {
            return $user->isProvincialUser()
                && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince()
                && $ticket->current_level === Ticket::LEVEL_PROVINCIAL
                && (int) $ticket->assigned_to === (int) $user->getKey();
        });

        Gate::define('ticketing.manageRegion', function ($user, Ticket $ticket): bool {
            $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable()
                : $user->normalizedRegion();
            $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
                ? $user->normalizedRegionComparable($ticket->region_scope)
                : strtolower(trim((string) $ticket->region_scope));

            if (!$user->isRegionalUser() || $ticketRegionComparable !== $userRegionComparable) {
                return false;
            }

            if (
                $ticket->status === Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE
                && (int) $ticket->forwarded_by === (int) $user->getKey()
            ) {
                return true;
            }

            return $ticket->current_level === Ticket::LEVEL_REGIONAL
                && (int) $ticket->assigned_to === (int) $user->getKey();
        });

        Gate::define('ticketing.manageAdmin', fn ($user) => $user->isSuperAdmin());
    }
}

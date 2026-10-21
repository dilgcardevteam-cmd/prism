<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Modules\Ticketing\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModularServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerClassAliases();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }

    /**
     * Register class aliases for backward compatibility across modules.
     */
    protected function registerClassAliases(): void
    {
        $aliases = [
            // Module aliases will be registered here as modules are refactored
        ];

        foreach ($aliases as $legacy => $new) {
            if (class_exists($new) && !class_exists($legacy, false)) {
                class_alias($new, $legacy);
            }
        }
    }

    /**
     * Register module policies.
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('ticketing.submit', [TicketPolicy::class, 'submit']);
        Gate::define('ticketing.view', [TicketPolicy::class, 'view']);
        Gate::define('ticketing.acceptProvince', [TicketPolicy::class, 'acceptProvince']);
        Gate::define('ticketing.acceptRegion', [TicketPolicy::class, 'acceptRegion']);
        Gate::define('ticketing.addComment', [TicketPolicy::class, 'addComment']);
        Gate::define('ticketing.manageProvince', [TicketPolicy::class, 'manageProvince']);
        Gate::define('ticketing.manageRegion', [TicketPolicy::class, 'manageRegion']);
        Gate::define('ticketing.manageAdmin', [TicketPolicy::class, 'manageAdmin']);
        Gate::define('ticketing.manageAdminTicket', [TicketPolicy::class, 'manageAdminTicket']);
        Gate::define('ticketing.pending', [TicketPolicy::class, 'pending']);
        Gate::define('ticketing.reopen', [TicketPolicy::class, 'reopen']);
        Gate::define('ticketing.resume', [TicketPolicy::class, 'resume']);
    }
}


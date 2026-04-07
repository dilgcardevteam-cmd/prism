<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketRoutingService
{
    public function hasProvincialHandlers(User $submitter): bool
    {
        $province = $submitter->normalizedProvince();

        if ($province === '') {
            return false;
        }

        return $this->provincialUsersQuery($province)->exists();
    }

    public function resolveProvincialAssignee(User $submitter): ?User
    {
        $province = $submitter->normalizedProvince();

        if ($province === '') {
            return null;
        }

        return $this->provincialUsersQuery($province)
            ->orderBy('lname')
            ->orderBy('fname')
            ->first();
    }

    public function resolveRegionalAssignee(Ticket $ticket): ?User
    {
        return $this->regionalRecipientsForTicket($ticket)->first();
    }

    public function resolveCentralOfficeAssignee(): ?User
    {
        return User::query()
            ->where('status', 'active')
            ->where('role', User::ROLE_SUPERADMIN)
            ->orderBy('lname')
            ->orderBy('fname')
            ->first();
    }

    public function provincialRecipientsForProvince(?string $province): Collection
    {
        $normalizedProvince = strtolower(trim((string) $province));

        if ($normalizedProvince === '') {
            return collect();
        }

        return $this->provincialUsersQuery($normalizedProvince)
            ->orderBy('lname')
            ->orderBy('fname')
            ->get();
    }

    public function regionalRecipientsForTicket(Ticket $ticket): Collection
    {
        $normalizedRegion = User::query()->make()->normalizedRegionComparable($ticket->region_scope);

        if ($normalizedRegion === '') {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->where('role', User::ROLE_REGIONAL)
            ->orderByRaw("
                CASE
                    WHEN LOWER(TRIM(COALESCE(province, ''))) = 'regional office' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('lname')
            ->orderBy('fname')
            ->get()
            ->filter(function (User $user) use ($normalizedRegion): bool {
                return $user->normalizedRegionComparable() === $normalizedRegion;
            })
            ->values();
    }

    protected function provincialUsersQuery(string $province)
    {
        return User::query()
            ->where('status', 'active')
            ->where('role', User::ROLE_PROVINCIAL)
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [$province]);
    }
}

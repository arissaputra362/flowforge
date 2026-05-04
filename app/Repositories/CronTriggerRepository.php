<?php

namespace App\Repositories;

use App\Models\Trigger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CronTriggerRepository
{
    /**
     * @return Collection<int, Trigger>
     */
    public function listEnabledCronTriggers(): Collection
    {
        return Trigger::query()
            ->where('type', 'cron')
            ->where('enabled', true)
            ->whereNotNull('config->cron_expression')
            ->with(['workflow.latestVersion'])
            ->get();
    }

    public function markTriggered(string $triggerId, Carbon $windowStart, Carbon $triggeredAt): bool
    {
        $updated = Trigger::query()
            ->where('id', $triggerId)
            ->where(function ($query) use ($windowStart) {
                $query->whereNull('last_triggered_at')
                    ->orWhere('last_triggered_at', '<', $windowStart);
            })
            ->update(['last_triggered_at' => $triggeredAt]);

        return $updated > 0;
    }
}

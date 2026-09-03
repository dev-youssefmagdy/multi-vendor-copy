<?php

namespace App\Console\Commands;

use App\Models\PaymentLog;
use App\Models\Tenant;
use App\Services\Mail\TemplateMailService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSubscriptionRenewalReminders extends Command
{
    protected $signature = 'subscriptions:send-renewal-reminders {--days=7 : Days before expiry to send reminders}';

    protected $description = 'Send subscription renewal reminder emails to tenants and the central admin.';

    public function handle(TemplateMailService $mailer): int
    {
        $daysAhead = (int) $this->option('days');
        $windowStart = now()->startOfDay();
        $windowEnd = now()->addDays($daysAhead)->endOfDay();

        $logs = PaymentLog::query()
            ->with(['package', 'package.tenants'])
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->get()
            ->filter(function (PaymentLog $log) use ($windowStart, $windowEnd) {
                if (!$log->paid_at || !$log->package?->term) {
                    return false;
                }

                $renewalDate = match ($log->package->term->value) {
                    'quarterly' => $log->paid_at->copy()->addMonths(3),
                    'yearly' => $log->paid_at->copy()->addYear(),
                    default => $log->paid_at->copy()->addMonth(),
                };

                return $renewalDate->between($windowStart, $windowEnd);
            });

        $sent = 0;

        foreach ($logs as $log) {
            $tenant = Tenant::query()->find($log->tenant_id);
            $package = $log->package;

            if (!$tenant || !$package) {
                continue;
            }

            $mailer->sendAdminSubscriptionRenewalReminder($tenant, $package, $log);
            $mailer->sendTenantSubscriptionRenewalReminder($tenant, $package, $log);

            $sent++;
            $this->line("Sent renewal reminders for tenant [{$tenant->id}] — {$tenant->email}");
        }

        $this->info("Done. Sent {$sent} renewal reminder pair(s).");

        return self::SUCCESS;
    }
}

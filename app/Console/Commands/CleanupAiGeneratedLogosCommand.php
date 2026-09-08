<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupAiGeneratedLogosCommand extends Command
{
    protected $signature = 'logos:cleanup-ai-generated';
    protected $description = 'Delete AI-generated logo files older than 1 day from each tenant\'s public storage';

    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        $cutoff = now()->subDay();
        $totalDeleted = 0;

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            $disk = Storage::disk('public');
            $deleted = 0;

            foreach ($disk->files('appearances/logos') as $path) {
                if (!str_starts_with(basename($path), 'ai_')) {
                    continue;
                }

                if ($disk->lastModified($path) <= $cutoff->getTimestamp()) {
                    $disk->delete($path);
                    $deleted++;
                }
            }

            tenancy()->end();

            if ($deleted > 0) {
                $this->line("✓ {$tenant->id}: removed {$deleted} logo(s)");
            }

            $totalDeleted += $deleted;
        }

        $this->info("Removed {$totalDeleted} AI-generated logo(s) older than 1 day.");

        return self::SUCCESS;
    }
}

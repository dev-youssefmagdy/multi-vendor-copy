<?php

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper;

/**
 * Vendor revert() reads originalPaths['disks'][$disk], which is only filled by
 * bootstrap(). If an earlier bootstrapper threw during initialize, tenancy is
 * left "initialized" and the next end() reverts a never-bootstrapped instance
 * ("Undefined array key local"). Make revert a no-op unless bootstrap ran.
 */
class SafeFilesystemTenancyBootstrapper extends FilesystemTenancyBootstrapper
{
    protected bool $bootstrapped = false;

    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant)
    {
        $this->bootstrapped = true;

        parent::bootstrap($tenant);
    }

    public function revert()
    {
        if (! $this->bootstrapped) {
            return;
        }

        $this->bootstrapped = false;

        parent::revert();
    }
}

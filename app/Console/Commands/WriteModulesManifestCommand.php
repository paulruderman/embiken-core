<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ModuleFrontendManifest;
use Illuminate\Console\Command;

class WriteModulesManifestCommand extends Command
{
    protected $signature = 'embiken:modules-manifest';

    protected $description = 'Write the frontend modules manifest from installed Embiken packages.';

    public function handle(ModuleFrontendManifest $manifest): int
    {
        $path = $manifest->write();
        $this->info("Wrote {$path}");

        return self::SUCCESS;
    }
}

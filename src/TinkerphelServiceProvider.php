<?php

declare(strict_types=1);

namespace Philbaker\Tinkerphel;

use Illuminate\Support\ServiceProvider;
use Philbaker\Tinkerphel\Console\NreplCommand;

final class TinkerphelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tinkerphel.php', 'tinkerphel');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([NreplCommand::class]);

        $this->publishes([
            __DIR__ . '/../config/tinkerphel.php' => $this->app->configPath('tinkerphel.php'),
        ], 'tinkerphel-config');
    }
}

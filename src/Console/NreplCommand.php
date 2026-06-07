<?php

declare(strict_types=1);

namespace Philbaker\Tinkerphel\Console;

use Illuminate\Console\Command;
use Philbaker\Tinkerphel\PhelRepl;
use Throwable;

/**
 * Starts a Phel nREPL server inside the already-booted Laravel application, so
 * forms evaluated from your editor run with the full container available —
 * Eloquent, config, facades, queues, everything.
 *
 * Because this runs as an artisan command, Laravel is already booted: there is
 * no need to require bootstrap/app.php or bootstrap the kernel manually.
 */
final class NreplCommand extends Command
{
    protected $signature = 'tinkerphel
        {--host= : Address to bind (default from config; 127.0.0.1)}
        {--port= : TCP port to listen on (default from config; 7888)}
        {--no-redefinition : Keep Phel\'s duplicate-definition guard (disables REPL redefinition)}';

    protected $description = 'Start a Phel nREPL server inside the booted Laravel app (connect with Conjure, etc.)';

    public function handle(): int
    {
        $host = (string) ($this->option('host') ?: config('tinkerphel.host', '127.0.0.1'));
        $port = (int) ($this->option('port') ?? config('tinkerphel.port', 7888));
        $allowRedefinition = ! $this->option('no-redefinition')
            && (bool) config('tinkerphel.allow_redefinition', true);

        $this->info(sprintf('Phel nREPL — Laravel %s', $this->laravel->version()));

        try {
            PhelRepl::serve(
                $this->laravel->basePath(),
                $host,
                $port,
                $allowRedefinition,
                fn (string $line) => $this->line($line),
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

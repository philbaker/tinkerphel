<?php

declare(strict_types=1);

namespace Philbaker\Tinkerphel\Console;

use Illuminate\Console\Command;
use Phel\Nrepl\NreplFacade;
use Phel\Phel as PhelRuntime;
use Phel\Shared\CompilerConstants;
use Phel\Shared\ReplConstants;
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

        // Phel/Gacela must be bootstrapped before its facades are usable. Safe
        // to call even if the host app already bootstrapped Phel itself.
        PhelRuntime::bootstrap($this->laravel->basePath());
        PhelRuntime::setupRuntimeArgs('nrepl', []);

        // Enable REPL semantics: re-evaluating (def …)/(defn …) redefines the
        // symbol instead of throwing DuplicateDefinitionException. The plain
        // `phel nrepl` command never does this; we mirror what `phel repl` does.
        // Set BEFORE loading namespaces so reloading an already-loaded ns is OK.
        // (\Phel is the global facade — __callStatic -> Registry — distinct from
        // the imported internal Phel\Phel.)
        $allowRedefinition = ! $this->option('no-redefinition')
            && (bool) config('tinkerphel.allow_redefinition', true);

        if ($allowRedefinition) {
            \Phel::addDefinition(
                CompilerConstants::PHEL_CORE_NAMESPACE,
                ReplConstants::REPL_MODE,
                true,
            );
        }

        $facade = new NreplFacade();
        $facade->loadPhelNamespaces();

        try {
            $server = $facade->createSocketServer(
                $port,
                $host,
                fn (string $line): bool => $this->output->writeln($line) ?? true,
            );
            $server->start();

            $this->info(sprintf(
                'Phel nREPL (Laravel %s) listening on %s:%d',
                $this->laravel->version(),
                $host,
                $server->port(),
            ));
            $this->line('Connect your editor via the bencode-over-TCP nREPL protocol. Press Ctrl-C to stop.');

            $server->run();

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

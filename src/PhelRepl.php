<?php

declare(strict_types=1);

namespace Philbaker\Tinkerphel;

use Phel\Nrepl\NreplFacade;
use Phel\Phel as PhelRuntime;
use Phel\Shared\CompilerConstants;
use Phel\Shared\ReplConstants;

/**
 * Starts a Phel nREPL server inside an already-booted Laravel application.
 *
 * Shared by the artisan command (Console\NreplCommand) and the standalone
 * global binary (bin/tinkerphel). The caller is responsible for booting Laravel
 * first; this just bootstraps Phel, enables REPL semantics, and runs the server.
 */
final class PhelRepl
{
    /**
     * @param (callable(string):void)|null $logger
     */
    public static function serve(
        string $basePath,
        string $host = '127.0.0.1',
        int $port = 7888,
        bool $allowRedefinition = true,
        ?callable $logger = null,
    ): void {
        // Phel/Gacela must be bootstrapped before its facades are usable. Safe
        // to call even if the host app already bootstrapped Phel itself.
        PhelRuntime::bootstrap($basePath);
        PhelRuntime::setupRuntimeArgs('nrepl', []);

        $facade = new NreplFacade();
        $facade->loadPhelNamespaces();

        // Enable REPL semantics AFTER loading namespaces (same order as the
        // built-in `phel repl`): re-evaluating (def …)/(defn …) then redefines
        // the symbol instead of throwing DuplicateDefinitionException. Setting
        // it *before* loadPhelNamespaces() makes Phel inject the project CWD
        // into ns-resolution and derails the cold namespace load (hang / "Cannot
        // resolve symbol 'comment'"). \Phel is the global facade (__callStatic
        // -> Registry), distinct from the imported internal Phel\Phel.
        if ($allowRedefinition) {
            \Phel::addDefinition(
                CompilerConstants::PHEL_CORE_NAMESPACE,
                ReplConstants::REPL_MODE,
                true,
            );
        }

        $server = $facade->createSocketServer($port, $host, $logger);
        $server->start();

        if ($logger !== null) {
            $logger(sprintf('Phel nREPL listening on %s:%d (Ctrl-C to stop)', $host, $server->port()));
        }

        $server->run();
    }
}

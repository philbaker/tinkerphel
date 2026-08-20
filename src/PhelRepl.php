<?php

declare(strict_types=1);

namespace Philbaker\Tinkerphel;

use Phel\Nrepl\NreplFacade;
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
        //
        // Everything here goes through the global \Phel, which is Phel's
        // documented public API. Its Phel\Phel base is marked @internal, and
        // the stability policy covers the members it declares only as reached
        // through the child — so importing the base directly would put us off
        // the supported surface.
        \Phel::bootstrap($basePath);
        \Phel::setupRuntimeArgs('nrepl', []);

        $facade = new NreplFacade();
        $facade->loadPhelNamespaces();

        // Enable interactive semantics AFTER loading namespaces, the same order
        // the built-in `phel nrepl` uses: re-evaluating (def …)/(defn …) then
        // redefines the symbol instead of throwing DuplicateDefinitionException.
        //
        // Redefinition needs *interactive-mode*, NOT *repl-mode*. Phel 0.50
        // moved the duplicate-definition gate off *repl-mode* (which only
        // `phel repl` sets) so `phel eval` and the nREPL server stopped raising
        // on a re-definition the prompt accepts (#2896). Setting only
        // *repl-mode* here would leave the guard armed, silently.
        //
        // *repl-mode* is still set alongside it, because it now does one other
        // thing: ReplReferInjector adds the phel.repl alias and its refers
        // (doc, source, require, macroexpand-1, …) to each analysed namespace,
        // so those stay callable unqualified from the editor.
        if ($allowRedefinition) {
            \Phel::addDefinition(
                CompilerConstants::PHEL_CORE_NAMESPACE,
                ReplConstants::INTERACTIVE_MODE,
                true,
            );
        }

        \Phel::addDefinition(
            CompilerConstants::PHEL_CORE_NAMESPACE,
            ReplConstants::REPL_MODE,
            true,
        );

        $server = $facade->createSocketServer($port, $host, $logger);
        $server->start();

        if ($logger !== null) {
            $logger(sprintf('Phel nREPL listening on %s:%d (Ctrl-C to stop)', $host, $server->port()));
        }

        $server->run();
    }
}

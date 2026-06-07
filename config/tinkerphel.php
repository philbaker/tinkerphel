<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Bind address
    |--------------------------------------------------------------------------
    |
    | Host/address the nREPL server listens on. Keep 127.0.0.1 for local use.
    | Inside a container (Laravel Sail/Docker) set this to 0.0.0.0 so the port
    | can be reached from the host, and publish the port in docker-compose.yml.
    |
    */

    'host' => env('TINKERPHEL_HOST', '127.0.0.1'),

    /*
    |--------------------------------------------------------------------------
    | Port
    |--------------------------------------------------------------------------
    |
    | TCP port for the bencode-over-TCP nREPL protocol. 7888 is the Phel
    | default. Use 0 to bind a random free port.
    |
    */

    'port' => (int) env('TINKERPHEL_PORT', 7888),

    /*
    |--------------------------------------------------------------------------
    | Allow redefinition
    |--------------------------------------------------------------------------
    |
    | Phel throws DuplicateDefinitionException when you redefine a symbol, to
    | catch accidental clobbering during file loads. That's the wrong default
    | for an interactive REPL, so we enable Phel's *repl-mode* which permits
    | re-evaluating (def …)/(defn …). Set to false to keep the guard.
    |
    */

    'allow_redefinition' => true,

];

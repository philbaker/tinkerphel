# Tinkerphel

A Tinkerwell-style [Phel](https://phel-lang.org) nREPL into a **live Laravel
application**. Start a server with `php artisan tinkerphel`, connect from your
editor (Conjure, etc.), and evaluate Phel forms against your fully-booted
app — Eloquent, config, facades, queues, the container, everything.

```clojure
(php/config "app.name")                        ; => "Laravel"
(php/:: \App\Models\User (count))               ; => 42
(def u (php/-> (php/:: \App\Models\User (factory)) (create)))
(php/-> u email)                                ; => "kemmer.rosalee@example.org"
```

## Why an artisan command?

`php artisan tinkerphel` runs inside an already-booted Laravel process, so there
is no manual `require bootstrap/app.php` / kernel bootstrapping. It also flips
Phel's `*repl-mode*` on, so re-evaluating a `(defn …)` **redefines** the symbol
instead of throwing `DuplicateDefinitionException` — the behaviour you expect
from a REPL but which the bare `phel nrepl` command does not provide.

## Requirements

- PHP 8.2+
- Laravel 10, 11 or 12

[Phel](https://github.com/phel-lang/phel-lang) (`^0.42`) is pulled in automatically as a dependency of this package

## Install

```bash
composer require philbaker/tinkerphel --dev
```

The service provider is auto-discovered. Optionally publish the config:

```bash
php artisan vendor:publish --tag=tinkerphel-config
```

## Usage

```bash
php artisan tinkerphel                      # 127.0.0.1:7888
php artisan tinkerphel --port=7899
php artisan tinkerphel --host=0.0.0.0       # bind all interfaces (containers)
php artisan tinkerphel --no-redefinition    # keep Phel's duplicate-def guard
```

Configure defaults via env: `TINKERPHEL_HOST`, `TINKERPHEL_PORT`.

## Global install (use on any project without touching its composer)

Don't want to add this to a shared project's `composer.json`/`composer.lock`?
Install it once, globally, and run it as a standalone command in any Laravel
project:

```bash
composer global require philbaker/tinkerphel   # brings Phel along too
```

Make sure Composer's global bin dir is on your `PATH` (commonly
`~/.composer/vendor/bin`), then from any project root:

```bash
cd any-laravel-project
tinkerphel                 # boots THIS project's Laravel, starts the nREPL
tinkerphel --port=7899
tinkerphel --host=0.0.0.0  # bind all interfaces (containers)
```

This leaves the project untouched: no `composer.json`/`composer.lock` change and
no `.gitignore` change. Phel's cache is redirected to your system temp dir (via
`GACELA_CACHE_DIR`), so no `.phel/` files are written into the working tree.

## Laravel Sail / Docker

The nREPL runs inside the container, so it must bind `0.0.0.0` and the port must
be published. Set `TINKERPHEL_HOST=0.0.0.0` (in `.env`) and add the port to
`docker-compose.yml` under the `laravel.test` service:

```yaml
        ports:
            - '${APP_PORT:-80}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
            - '${FORWARD_NREPL_PORT:-7888}:7888'   # add this, then `sail up -d`
```

Then run:

```bash
./vendor/bin/sail artisan tinkerphel --host=0.0.0.0
```

## Editor setup (Conjure / Neovim)

Phel's nREPL speaks standard bencode-over-TCP and advertises the ops Conjure's
Clojure client needs (`eval`, `load-file`, `complete`, `info`, `eldoc`,
`lookup`, `describe`, `interrupt`). There is no dedicated Phel client in
Conjure, so reuse the Clojure one for `.phel` buffers.

**Lua:**

```lua
vim.filetype.add({ extension = { phel = "phel" } })
vim.g["conjure#filetypes"] = vim.list_extend(vim.g["conjure#filetypes"] or {}, { "phel" })
vim.g["conjure#filetype#phel"] = "conjure.client.clojure.nrepl"
```

**Vimscript:**

```vim
autocmd BufRead,BufNewFile *.phel setfiletype phel
let g:conjure#filetypes = add(get(g:, 'conjure#filetypes', []), 'phel')
let g:conjure#filetype#phel = 'conjure.client.clojure.nrepl'
```

**Fennel (nfnl):**

```fennel
(vim.filetype.add {:extension {:phel :phel}})
(set nvim.g.conjure#filetypes
     ["clojure" "fennel" "phel"])          ; keep the defaults you use
(set nvim.g.conjure#filetype#phel "conjure.client.clojure.nrepl")
```

Then open a `.phel` file and `:ConjureConnect 7888` (host defaults to
`127.0.0.1`). Drop a `.nrepl-port` file (`echo 7888 > .nrepl-port`) for
auto-connect.

## Interop quick reference

| You want | Phel |
|---|---|
| Global fn / Laravel helper | `(php/config "app.name")` |
| Static / Eloquent | `(php/:: \App\Models\User (all))` |
| Instance method (chains) | `(php/-> $q (where "id" 1) (first))` |
| Property read (no parens) | `(php/-> u email)` |
| Construct | `(php/new \DateTime "now")` |

**Printing:** Phel's REPL printer can't render `stdClass` / Eloquent
models/collections — convert at the edge:

```clojure
(php/-> (php/:: \App\Models\User (all)) (pluck "email") (toArray))
(php/dump some-model)
```

## License

MIT

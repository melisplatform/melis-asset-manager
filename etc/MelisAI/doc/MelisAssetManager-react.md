---
title: MelisAssetManager module — React back-office
package: melisplatform/melis-asset-manager
doc_type: module-documentation-react
audience: [users, developers, ai]
language: en
module_version: unversioned
last_reviewed: 2026-08-19
maintainer: Melis Technology
keywords: [asset manager, react, bundle, ui-react, meliscore, infrastructure, back-office, module-assets, public, hashed-assets, webpack, module-discovery]
related_docs: [./MelisAssetManager.md]
---

# MelisAssetManager (React back-office) — Infrastructure Role Documentation (for AI)

> **What this is.** MelisAssetManager has **no React back-office tool and no UI of its own** —
> there is no `ui-react/` brick source, no `public/ui-react/brick.manifest.json`, no
> `config/react-api.php` and no `config/react.capabilities.php`. It never appears as a tool in
> `/melis-react`. This document exists to explain its **infrastructure role** in the React
> back-office: it is the mechanism that **serves the compiled React SPA bundle** (the files
> committed under `melis-core/public/ui-react/`) to the browser under the base URL
> **`/MelisCore/ui-react/`**. Without it, `/melis-react` cannot load its JS/CSS.
>
> For the full asset-manager feature set (asset serving, webpack bundling, module discovery),
> read the **[legacy doc](./MelisAssetManager.md)** — this file does not repeat it, it only
> covers the React relationship.
>
> **How this document is organised — two clearly separated parts:**
> - **[Part A — Functional](#part-a--functional)** — plain language: why an invisible module
>   underpins the whole React back-office.
> - **[Part B — Technical](#part-b--technical)** — the real serving mechanism with accurate
>   class/method/config names.
>
> **Audience**: consumed by the **MelisAI** MCP. **Status**: reviewed 2026-08-19.

---

## 0. Where this lives in the React back-office — read this first

**Brick kind: none.** MelisAssetManager is **not migrated to React** and is **not meant to be** —
it is platform infrastructure, not a tool. It:

- has **no brick** (no `ui-react/` project, no `public/ui-react/brick.manifest.json`),
- exposes **no `react-api` endpoints** (no `config/react-api.php`),
- declares **no capabilities** (no `config/react.capabilities.php`),
- shows **no sidebar entry** and **no menu node** in `/melis-react`.

What it *does* for React: it is the HTTP delivery layer that makes the **committed React build**
(`melis-core/public/ui-react/`) reachable in the browser at **`/MelisCore/ui-react/…`** — the
exact base the Vite build is compiled against. This is the same load-time asset resolver that
serves every module's `public/` assets (`/<ModuleName>/…`); the React bundle is just one more
consumer of it (module `MelisCore`, folder `public/ui-react/`).

> ⚠ If MelisAssetManager (or the writable cache it maintains) fails, the React shell HTML may
> still be delivered but its hashed JS/CSS return 404 or the wrong MIME type → **blank
> `/melis-react`**. See §A3 and the [legacy doc](./MelisAssetManager.md) §A1.

Cross-links: [MelisAssetManager legacy doc](./MelisAssetManager.md) · React shell served by
**MelisReactOverride**, React JSON API in **MelisReactApi** (both separate modules).

---
---

# PART A — Functional

## A1. What it is (and why you never see it)

End users and back-office admins **never open MelisAssetManager** — there is no screen for it,
in the legacy back-office *or* in `/melis-react`. It runs automatically at every request. In the
context of the React back-office, its single relevant job is: **deliver the compiled React app's
files to the browser** so `/melis-react` can boot.

## A2. How it silently underpins `/melis-react`

The React back-office is a Single-Page App built with Vite. Its compiled output (JavaScript,
CSS, fonts, icons, `index.html`) is **committed into the repository** under
`vendor/melisplatform/melis-core/public/ui-react/`. When the browser requests those files at
URLs beginning with **`/MelisCore/ui-react/`**, MelisAssetManager is what intercepts the request
and streams the file from `melis-core/public/…`.

- The React **shell page** is reachable at **`/melis-react`** (served by MelisReactOverride).
- The React **assets** are reachable at **`/MelisCore/ui-react/…`** (served by MelisAssetManager),
  which is the exact base URL the Vite build was compiled against.

So the split is: MelisReactOverride serves the HTML shell; **MelisAssetManager serves the
hashed asset bundle** that shell loads.

## A3. The common symptom when it fails

If MelisAssetManager cannot serve the React assets, the typical symptom is a **blank React
back-office** — the page loads but stays empty — because the SPA's JS/CSS returned **404** or an
incorrect **MIME type** (so the browser refuses to execute the script). The usual root causes:

- **Permissions on `config/`** — the module maintains a cache file
  `config/melis.modules.path.php` and must be able to write it; if that folder/file isn't
  writable by the web user (e.g. `www-data`), a warning is emitted instead of the asset (wrong
  MIME) and the module script never runs. (See the memory note *react-blank-modulepath-cache-perms*.)
- **A newly-activated module** forces a rebuild of that cache; if the rebuild fails on
  permissions, asset serving degrades.

> **In short:** MelisAssetManager is invisible but load-bearing for `/melis-react`. A blank React
> BO with 404/MIME errors on `/MelisCore/ui-react/*` almost always points here (or at `config/`
> file permissions).

---
---

# PART B — Technical

## B1. Metadata & dependencies

| Item | Value |
|---|---|
| Package | `melisplatform/melis-asset-manager` · type `melisplatform-module` · category `core` · namespace `MelisAssetManager\` |
| Requires | `php` `^8.3\|^8.5`, `melisplatform/melis-core` `^6.0` |
| React presence | **None** — no brick, no `react-api`, no capabilities, no UI |
| React relationship | Serves the compiled React SPA bundle under base URL `/MelisCore/ui-react/` |

## B2. The serving mechanism — `MelisAssetManager\Module`

There is **no controller** for the common asset path — serving is a **load-time resolver**
wired in `src/Module.php`:

- **`onBootstrap(MvcEvent $e)`** calls **`displayFile($sm)`** on every request.
- **`displayFile($sm)`** resolves the request URI to a file and streams it:
  1. First it tries the project's **main public folder**: `$_SERVER['DOCUMENT_ROOT'] . $uri`.
  2. Otherwise it treats the **first URI segment as a module name**
     (`$moduleUri = $detailUri[1]`), looks that module up in the cache map, and builds the file
     path as **`<modulePath>/public/<rest-of-URI>`**.
- **`sendDocument($pathFile, $UriParams, $sm)`** sets the correct `Content-Type` (via
  `getMimeType()` + `config/mime.config.php`), adds a 24h cache header for static files, prints
  the bytes and `die`s. (A security guard `isRequestAuthenticated()` requires a valid session
  before ever `eval`-ing a served `.php` file; static assets stay public.)
- **`checkFileInFolder()`** enforces that the resolved path stays inside the module's
  `public/` directory (path-traversal guard).

**How this serves the React build (the `/MelisCore/ui-react/` mapping):**
A request for e.g. `/MelisCore/ui-react/assets/index-<hash>.js` is split on `/`; the first
segment **`MelisCore`** is the module name. `displayFile()` resolves `MelisCore`'s path from the
cache map and appends **`/public/ui-react/assets/index-<hash>.js`** → it streams
`vendor/melisplatform/melis-core/public/ui-react/assets/index-<hash>.js`. That directory is the
**committed Vite build**, and `/MelisCore/ui-react/` is exactly the **Vite `base`** the build is
compiled against (`melis-core/ui-react/vite.config.ts`:
`base: … '/MelisCore/ui-react/'`). No React-specific code exists here — the React bundle is just
the `MelisCore` module's `public/ui-react/` folder, served by the same generic `/<Module>/…` →
`<module>/public/…` mapping used for every module's assets.

## B3. The module-path cache — `config/melis.modules.path.php`

The `<ModuleName> → path` map used by `displayFile()` is a generated PHP file. It is (re)built by
the module-load listener in `src/Module.php`:

- **`init(ModuleManager $manager)`** attaches **`onLoadModulesPost()`** to
  `ModuleEvent::EVENT_LOAD_MODULES_POST`.
- **`onLoadModulesPost()`** writes **`$_SERVER['DOCUMENT_ROOT'] . '/../config' . '/melis.modules.path.php'`**
  (property `$modulePathFile = '/melis.modules.path.php'`) when the file is missing **or** a
  newly-activated module isn't yet in it. It uses **`MelisAssetManagerModulesService`**
  (`getAllModules()`, `getActiveModules()`, `getSitesModules()`, `getModulePath()`) to compute
  each module's path (stored relative to the project root), `fwrite`s the `return array(...)`,
  then `chmod($modulePathFile, 0777)`.

> ⚠ This file (and its `config/` folder) **must be writable** by the web user. When a new module
> is activated the cache is regenerated; a permission failure here is the classic cause of a
> **blank `/melis-react`** (asset served as a PHP warning → wrong MIME → the React script is not
> executed). Fix locally: `chown www-data` the cache file.

## B4. WebPack / bundling — relates to the LEGACY bundle, not the React build

`src/Controller/WebPackController.php` (routes `melis-backoffice/build-webpack` and
`melis-backoffice/view-assets`, controller alias `MelisAssetManager\Controller\WebPack`) drives
**`MelisAssetManagerWebPack`** (`MelisWebPackService`):

- `buildWebpackAction()` → `webpack()->buildWebPack()` compiles the platform's declared
  `bundle.css` / `bundle.js` (each module's `ressources.build` config in `app.interface.php`).
- `viewAssetsAction()` → `webpack()->getAssets()` lists declared assets.

This is the **legacy back-office bundling pipeline** — it has **nothing to do with the React
build**. The React SPA is built by **Vite** inside `melis-core/ui-react/` (`npm run build`) and
committed to `melis-core/public/ui-react/`; MelisAssetManager only **serves** those already-built
files (§B2), it does not compile them. Do not conflate `MelisWebPackService` with the React
bundle.

## B5. Quick code map (React-relevant parts)

```
melis-asset-manager/
├── composer.json                          → type melisplatform-module, category core, requires melis-core ^6.0
├── config/
│   ├── module.config.php                  → service aliases (MelisAssetManagerModulesService, …WebPack, MelisConfig),
│   │                                        WebPack routes/controller, icon view helpers  (NO react-api / react.capabilities)
│   └── mime.config.php                     → extension → MIME map used by sendDocument()
├── src/
│   ├── Module.php                          → THE serving mechanism:
│   │                                        onBootstrap→displayFile()  (URI → file, incl. /MelisCore/ui-react/… → melis-core/public/ui-react/…)
│   │                                        init→onLoadModulesPost()   (writes/refreshes config/melis.modules.path.php)
│   │                                        sendDocument() / getMimeType() / checkFileInFolder() / isRequestAuthenticated()
│   ├── Controller/WebPackController.php     → LEGACY bundle only (buildWebpack / viewAssets) — unrelated to the React build
│   └── Service/                             → MelisModulesService (module discovery), MelisWebPackService (legacy bundle), MelisConfigService
└── etc/MelisAI/doc/
    ├── MelisAssetManager.md                 → legacy/full doc (cross-linked)
    └── MelisAssetManager-react.md           → THIS doc (infrastructure role in the React BO)

(no ui-react/, no public/ui-react/, no config/react-api.php, no config/react.capabilities.php)

Served elsewhere (the React build this module delivers):
melis-core/public/ui-react/               → committed Vite build (index.html, assets/, icons.svg, favicon.svg)
melis-core/ui-react/vite.config.ts        → base '/MelisCore/ui-react/'  (matches the serving URL above)
```

---

*Document for AI consumption (MelisAI MCP) — infrastructure role of
`melisplatform/melis-asset-manager` in the React back-office. This module has no React tool/UI;
it serves the compiled React SPA bundle under `/MelisCore/ui-react/`. Full asset-manager
reference: [./MelisAssetManager.md](./MelisAssetManager.md). Last reviewed 2026-08-19.*

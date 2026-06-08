---
title: MelisAssetManager module
package: melisplatform/melis-asset-manager
doc_type: module-documentation
audience: [users, developers, ai]
language: en
module_version: unversioned
last_reviewed: 2026-06-08
maintainer: Melis Technology
keywords: [assets, css, js, images, public, module-assets, webpack, bundle, module-discovery, melis, core, foundation]
screenshots_dir: ./images
---

# MelisAssetManager — Functional & Technical Documentation (for AI)

> **What this is.** MelisAssetManager **delivers every module's public assets** (CSS, JS,
> images) to the browser over clean URLs, **bundles** them for production (webpack), and is the
> canonical source of **"which modules are installed/active"** on the platform. It is part of the
> platform foundation (see §0) and is invisible to end-users — but it's why every module's styles
> and scripts load.
>
> **Two parts:** **[Part A — Functional Guide](#part-a--functional-guide)** ·
> **[Part B — Technical Reference](#part-b--technical-reference)** (developers/AI, with examples).
> Consumed by the **MelisAI** MCP. **No screenshots** — headless infrastructure. Reviewed 2026-06-08.

---

## 0. The MelisCore platform foundation (this family of modules)

> These modules are the **foundation of the Melis platform** — collectively referred to as
> **"MelisCore"**. *MelisCore* proper is the back-office heart everything depends on; the other
> four are the infrastructure that installs, deploys, serves and migrates the platform. When
> working on or asking about one, the others are often relevant — follow the cross-links.

- **MelisCore** — the **back-office foundation**: login & sessions, users/roles/rights, the menu
  & tool (DataTable) framework, the dashboard, translations, config, email, GDPR, and the base
  service + event system every module extends. **Every module depends on it.**
  → [MelisCore doc](../../../melis-core/etc/MelisAI/doc/MelisCore.md)
- **MelisAssetManager** *(this module)* — **serves each module's public assets** (CSS/JS/images)
  over URLs and bundles them; discovers the active modules.
- **MelisDbDeploy** — **applies database migrations** (each module's `install/dbdeploy/*.sql`).
  → [MelisDbDeploy doc](../../../melis-dbdeploy/etc/MelisAI/doc/MelisDbDeploy.md)
- **MelisComposerDeploy** — **runs Composer from inside the platform** to install/update/remove
  modules. → [MelisComposerDeploy doc](../../../melis-composerdeploy/etc/MelisAI/doc/MelisComposerDeploy.md)
- **MelisInstaller** — the **first-run installer** wizard.
  → [MelisInstaller doc](../../../melis-installer/etc/MelisAI/doc/MelisInstaller.md)

**Dependency note:** MelisAssetManager and MelisInstaller require MelisCore (installer also
MelisEngine); MelisDbDeploy and MelisComposerDeploy are low-level standalone tools (depend only on
phing / composer) that MelisCore, the installer and the marketplace drive.

---
---

# PART A — Functional Guide

## A1. What it does for you (invisibly)

You never open MelisAssetManager — it works automatically. It is responsible for:

- **Loading every module's look & behaviour** — the CSS, JavaScript and images that each module
  ships are served to the browser so the back-office and your sites display correctly.
- **Fast production pages** — it can **bundle** all of a platform's CSS/JS into compiled files
  (webpack), so the browser makes fewer, smaller requests.
- **Knowing what's installed** — it computes the list of **active modules** that the back-office's
  Modules/Marketplace screens and other features rely on.

If a module's styles or scripts ever fail to load, MelisAssetManager (and file permissions on the
`config/` folder) is usually where the problem lies.

## A2. Good to know (for admins)

- Module assets are reachable at **`/<ModuleName>/css/…`, `/<ModuleName>/js/…`,
  `/<ModuleName>/images/…`** — these URLs map to each module's `public/` folder.
- On install it writes a small map file at **`/config/melis.modules.path.php`** (so that folder
  must be writable).
- Whether production **bundling** is on is a per-platform setting.

---
---

# PART B — Technical Reference

## B1. Metadata & dependencies

| Item | Value |
|---|---|
| Package | `melisplatform/melis-asset-manager` · category `core` · namespace `MelisAssetManager\` |
| Requires | `melisplatform/melis-core` (`^5.2`) |

## B2. How assets are served

A **listener attached at module load** intercepts requests to `/<ModuleName>/{css,js,images}/…`
and streams the file from that module's `public/` directory (falling back to the project's main
`public/`). The module→path map is persisted to `/config/melis.modules.path.php` (the `config/`
folder must be writable). No DB, no controller logic for the common path — it's a load-time
resolver.

## B3. Module discovery — `MelisModulesService` (with examples)

The platform's canonical "what modules exist / are active" service. Used widely (Modules tool,
marketplace, site module loading, installer):

```php
$modules = $sm->get('ModulesService');  // MelisAssetManager\Service\MelisModulesService

$active  = $modules->getMelisActiveModules();   // modules currently enabled
$all     = $modules->getAllModules();           // every discoverable module
$vendor  = $modules->getVendorModules();         // modules under vendor/
$versions= $modules->getModulesAndVersions();    // module => version
$deps    = $modules->getChildDependencies($moduleName);
$site    = $modules->getSitesModules();          // template/site modules
```

Methods: `getMelisActiveModules`, `getModulesAndVersions`, `getComposer`/`setComposer`,
`getUserModules`, `getSitesModules`, `getMelisModules`, `getAllModules`, `getVendorModules`,
`getChildDependencies`.

## B4. Production bundling — `MelisWebPackService`

```php
$webpack = $sm->get('MelisWebPackService');
$assets  = $webpack->getAssets($moduleName);          // a module's declared assets
$merged  = $webpack->getMergedAssets();               // platform-wide merged set
$webpack->buildWebPack();                              // compile bundles
$file    = $webpack->getWebPackMixStaticFile($asset); // resolve a hashed/mixed asset
```

Methods: `getAssets`, `getWebPackMixStaticFile`, `getMergedAssets`, `buildWebPack`,
`setCachedFile`, `getCachedFiles`. The per-module `ressources.build` config (seen in many modules'
`app.interface.php`) declares the `bundle.css` / `bundle.js` that this service produces/serves.

## B5. Config helper

`MelisConfigService` — a config-merge/translation helper (`getItem`, `getMelisKeys`,
`getFormMergedAndOrdered`, `translateAppConfig`, …) mirroring core's config service for
asset-manager's own needs.

## B6. Quick code map

```
melis-asset-manager/
├── composer.json                 → deps (core), category core
├── config/module.config.php      → service aliases, the asset-serving wiring
├── src/   Controller/ (asset/webpack) · Service/ (MelisModulesService, MelisWebPackService, MelisConfigService) · View/
├── public/
└── etc/   MarketPlace + MelisAI/doc (this doc)
```

---

*Document for AI consumption (MelisAI MCP) — `melisplatform/melis-asset-manager`. Part A =
functional; Part B = technical with examples. Part of the MelisCore platform foundation. Last
reviewed 2026-06-08.*

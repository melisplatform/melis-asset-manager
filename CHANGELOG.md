# Changelog

## v6.0.4 - 2026-09-23
### Added
* **security:** expose the service manager to the PHP files served by the asset manager
### Fixed
* **security:** never create a PHP session for a junk-file 404 (DEKRA #20 follow-up)
* **security:** 404 and exit for non-existent file-extension requests (DEKRA #20)

## v6.0.3 - 2026-08-20
### Fixed
* **asset-manager:** mini templates 403'd for logged-in users
### Docs
* **melisai:** React back-office AI documentation for MelisAssetManager

## v6.0.2 - 2026-08-10
### Dependencies & build
* **composer:** update docs/homepage links, swap zf2 keyword for laminas, bump php constraint to ^8.3|^8.5

## v6.0.1 - 2026-08-10
* Maintenance release.

## v6.0.0 - 2026-08-10
### Security
* **security:** add SECURITY.md (private vulnerability reporting policy)
* Fix audit findings
### Added
* **melis-asset-manager:** add MelisAI two-part documentation (functional guide + technical reference)
### Fixed
* **security:** harden legacy file/dir creation & output escaping
### Dependencies & build
* **deps:** require melisplatform/melis-core ^6.0

## v5.3.4 - 2026-05-13
### Added
* Added function to get all AI modules and its path
### Changed
* Removed unnecessary comment

## v5.3.3 - 2025-10-01
* Maintenance release.

## v5.3.2 - 2025-02-12
### Changed
* Updated back office sidebar menu module svg icons

## v5.3.1 - 2024-10-21
### Dependencies & build
* Update bundle rendering

## v5.3.0 - 2024-09-25
* Maintenance release.

## v5.2.1 - 2024-07-25
### Added
* Add get translations to load separately in the url

## v5.2.0 - 2024-06-06
### Added
* Add checker on on table platform service
* Added bundle cache time
### Changed
* Update rendering of assets
### Dependencies & build
* Update making generated bundle directory
* Clean code and add time on bundle files

## v5.1.0 - 2024-02-13
### Fixed
* Fixed problem building minified assets
### Changed
* Change melis core version to 5.1
* Passing null on substr error

## v5.0.1 - 2022-09-26
### Added
* Added checking if the requested file is inside the intended directory

## v5.0.0 - 2022-06-22
### Added
* Added php8 as one of the required php versions

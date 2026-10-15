# Changelog

All notable changes to HtmlPlucker will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] — 2026-10-15

### Fixed

- `MockHttpClient` war für Projekte, die HtmlPlucker als Abhängigkeit nutzen,
  nicht ladbar (lag in `tests/`) — jetzt `HtmlPlucker\Testing\MockHttpClient`
- Ungültige CSS-Selektoren warfen eine native `\DOMException` an der
  Exception-Hierarchie vorbei — jetzt `SelectorException`
- `Document::fromFile()` mit einem Verzeichnis führte zu Warning und
  `ParseException` — jetzt `FileNotFoundException`
- `GuzzleHttpClient`: eigener `user-agent`-Header (beliebige Schreibweise)
  wurde an den Default angehängt statt ihn zu ersetzen

### Changed

- `Node::text()` liefert normalisierten Text: Whitespace inkl. `&nbsp;`
  zusammengefasst und getrimmt, Block-Elemente und `<br>` trennen Wörter,
  `<script>`/`<style>`/`<template>` werden ignoriert

### Added

- `Node::rawText()` — unverändertes `textContent`
- `SelectorException`
- `.gitattributes` — Tests und CI-Dateien sind nicht mehr Teil der
  Composer-Dist-Archive

## [1.0.0] — 2026-05-20

### Added

- `Document::fromString()` — parst HTML aus einem String
- `Document::fromUrl()` — lädt HTML via Guzzle HTTP Client
- `Document::fromFile()` — liest HTML von der Festplatte
- `Document::first()`, `all()`, `has()`, `expect()` — CSS-Selektor-Suche
- `Node` — wrappet `\Dom\Element`, stellt Read-API bereit
  - `text()`, `html()`, `tag()` — Inhalt
  - `attr()`, `attrs()` — Attribute
  - `first()`, `all()`, `has()`, `expect()` — Suche im Teilbaum
  - `parent()`, `children()`, `next()`, `prev()` — Traversierung
- `HttpClientInterface` — austauschbarer HTTP-Client Vertrag
- `GuzzleHttpClient` — Standard-Implementierung via Guzzle ^7.9
- `MockHttpClient` — In-Memory HTTP Client für Tests
- Exception-Hierarchie: `HtmlPluckerException`, `LoadException`,
  `NetworkException`, `FileNotFoundException`, `ParseException`,
  `NodeNotFoundException`
- 48 Unit-Tests (PHPUnit 11)
- GitHub Actions CI auf PHP 8.4

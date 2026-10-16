# Changelog

All notable changes to HtmlPlucker will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] — 2026-10-16

### Fixed

- `Node::tag()` lieferte SVG- und MathML-Elementnamen in Kleinschreibung
  (`foreignobject`) — jetzt in korrekter Schreibweise (`foreignObject`);
  HTML-Elemente sind unverändert kleingeschrieben

## [1.2.0] — 2026-10-15

### Added

- Größenlimit für `Document::fromUrl()` und `fromFile()`, Default **10 MiB**
  (`Limits::DEFAULT_MAX_BYTES`), `null` = unbegrenzt
  - URLs: Konstruktor-Parameter `GuzzleHttpClient(maxBytes: …)`; ein zu großes
    `Content-Length` bricht vor dem Body ab, sonst bricht der Transfer ab,
    sobald der *dekodierte* Body das Limit überschreitet (auch bei fehlendem
    `Content-Length` und bei komprimierten „Zip-Bomben“)
  - Dateien: neuer optionaler Parameter `fromFile(…, maxBytes: …)`;
    die Dateigröße wird vor dem Lesen geprüft
- `SizeLimitExceededException` (unter `LoadException`) mit `$limit` und
  `$actualSize` (bekannte Gesamtgröße oder `null`)
- `Limits::DEFAULT_MAX_BYTES`

### Changed

- Dokumente über 10 MiB werden ohne explizite Konfiguration abgelehnt —
  bisher wurden sie vollständig in den Speicher geladen

## [1.1.0] — 2026-10-15

> **Enthält Breaking Changes** — bewusst in einer Minor-Version, da noch keine
> Projekte auf HtmlPlucker aufbauen. Siehe README, Abschnitt „Upgrading from 1.0".

### Changed — BREAKING

- `guzzlehttp/guzzle` `^8.0` statt `^7.9`
- `HttpClientInterface::get()` gibt `HttpResponse` statt `string` zurück —
  eigene Implementierungen müssen angepasst werden; wer nur
  `Document::fromUrl()` nutzt, ist nicht betroffen

### Added

- `Node::url($attribute = 'href')` — URL-Attribute als absolute URL; Basis ist
  das erste `<base href>`, aufgelöst gegen die Dokument-URL
- `Document::fromString()`/`fromFile()`: optionaler Parameter `$baseUrl`;
  `fromUrl()` nutzt die finale URL nach Redirects als Basis
- `Document::fromString()`/`fromFile()`: optionaler Parameter `$charset`;
  `fromUrl()` übernimmt das Charset aus dem `Content-Type`-Header.
  Reihenfolge wie in der HTML-Spezifikation: BOM > Charset > `<meta charset>` > UTF-8
- `HttpResponse` mit `body`, `statusCode`, `charset`, `effectiveUrl`
- `NetworkException::$statusCode` — Status bei HTTP-Fehlern (4xx/5xx), sonst `null`
- `MockHttpClient::addResponse()`/`addDefaultResponse()`: optionaler `$charset`
- `guzzlehttp/psr7` `^3.1` als direkte Abhängigkeit

### Fixed

- Seiten, die ihre Kodierung nur im HTTP-Header angeben, wurden falsch dekodiert

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

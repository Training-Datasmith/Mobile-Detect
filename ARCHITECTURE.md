# Architecture: Mobile-Detect

## Purpose

Lightweight PHP library for detecting mobile devices (phones and tablets) using the User-Agent string and specific HTTP headers. Provides both Composer-based and standalone (no-Composer) distributions.

## Directory Structure

```
src/
  Mobile_Detect.php          Main detection class: regex patterns, magic isXXX() methods, isMobile(), isTablet()
  Mobile_Detect_Standalone.php  Extends Mobile_Detect; autoloads standalone deps from standalone/
  Cache/
    Cache.php                  In-memory PSR-16 cache implementation with TTL
    Cache_Exception.php
    Cache_Invalid_Argument_Exception.php
  Exception/
    Mobile_Detect_Exception.php
    Mobile_Detect_Exception_Code.php  Enum of exception codes
standalone/
  deps/simple-cache/           Vendored PSR-16 cache interface for standalone usage
  autoloader.php               Simple autoloader for standalone distribution
MobileDetect.json              Machine-readable device patterns and version information
scripts/                       Developer utilities (dump magic methods, export JSON)
tests/
  providers/vendors/           Per-vendor User-Agent fixtures for data-driven tests
  benchmark/                   PHPBench performance tests
```

## Key Design Decisions

- **Static pattern arrays**: Device, tablet, and OS regex patterns are defined as static class properties so they are shared across instances and can be extended by subclassing.
- **PSR-16 caching**: Regex match results are cached per User-Agent hash. The cache implementation is swappable (pass any `CacheInterface` to the constructor).
- **Magic methods**: `isXXXX()` methods are generated via `__call()` by matching the method name suffix against the pattern keys. This avoids hundreds of explicit method definitions.
- **Standalone mode**: `Mobile_Detect_Standalone` includes a bundled autoloader and PSR-16 polyfill so the library works without Composer.

## Extension Points

- Pass a custom `CacheInterface` implementation to `__construct()` to change caching behaviour.
- Override `getPhoneDevices()`, `getTabletDevices()`, `getOperatingSystems()`, or `getBrowsers()` in a subclass to add custom patterns.
- Set `autoInitOfHttpHeaders = false` and call `setHttpHeaders()` manually for non-`$_SERVER` environments.

## Dependency Flow

```
HTTP request
  -> Mobile_Detect::__construct(headers, userAgent)
    -> setHttpHeaders() from $_SERVER
    -> setUserAgent() from HTTP_USER_AGENT
  -> isMobile() / isTablet() / isXXX()
    -> Cache::get(sha1(userAgent + method))
      hit  -> return cached bool
      miss -> matchUAAgainstKey() -> regex match
           -> Cache::set(...)
           -> return bool
```

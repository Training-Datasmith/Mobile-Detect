<?php

declare(strict_types=1);

/**
 * Mobile-Detect — basic device detection example.
 *
 * Demonstrates: mobile/tablet detection, specific device checks, version extraction.
 *
 * Run from the project root:
 *   php examples/basic_detection.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Detection\Mobile_Detect;

// Instantiate with default headers from $_SERVER
$detect = new Mobile_Detect();

// --- Simulate a mobile user-agent for the example ---
$detect->setUserAgent(
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) ' .
    'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
);

echo 'Is mobile: ' . ($detect->isMobile() ? 'yes' : 'no') . PHP_EOL;
echo 'Is tablet: ' . ($detect->isTablet() ? 'yes' : 'no') . PHP_EOL;
echo 'Is iPhone: ' . ($detect->isiPhone() ? 'yes' : 'no') . PHP_EOL;
echo 'Is Android: ' . ($detect->isAndroidOS() ? 'yes' : 'no') . PHP_EOL;

// Extract iOS version
$version = $detect->version('iOS');
echo 'iOS version: ' . ($version !== false ? $version : 'n/a') . PHP_EOL;

// --- Now try a desktop user-agent ---
$detect->setUserAgent(
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
    'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
);

echo PHP_EOL . 'Desktop user-agent:' . PHP_EOL;
echo 'Is mobile: ' . ($detect->isMobile() ? 'yes' : 'no') . PHP_EOL;
echo 'Is tablet: ' . ($detect->isTablet() ? 'yes' : 'no') . PHP_EOL;

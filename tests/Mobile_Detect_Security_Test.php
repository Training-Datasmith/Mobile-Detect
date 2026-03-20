<?php

declare(strict_types=1);

/*
 * Security regression tests for Mobile_Detect.
 *
 * These tests validate that User-Agent length limiting and safe header
 * handling work correctly to prevent resource exhaustion and injection attacks.
 */

namespace Detection\Tests;

use Detection\Mobile_Detect;
use PHPUnit\Framework\TestCase;

/**
 * Security-focused tests for Mobile_Detect.
 *
 * Validates:
 * - Oversized User-Agent strings are truncated to the configured maximum length
 * - A crafted very long UA string does not cause excessive regex backtracking
 * - Detection result is still consistent after truncation
 *
 * @covers \Detection\Mobile_Detect
 */
class Mobile_Detect_Security_Test extends TestCase
{
    /**
     * A User-Agent longer than the configured maximumUserAgentLength should be
     * silently truncated rather than processed in full.
     *
     * This prevents a crafted oversized UA from causing catastrophic backtracking
     * or excessive memory usage in the regex engine.
     */
    public function testOversizedUserAgentIsTruncatedToMaximumLength(): void
    {
        $detect     = new Mobile_Detect();
        $maxLength  = 500; // default maximumUserAgentLength

        // Craft a UA that is much longer than the allowed maximum
        $oversizedUa = str_repeat('A', 10000);

        $detect->setUserAgent($oversizedUa);
        $storedUa = $detect->getUserAgent();

        $this->assertLessThanOrEqual(
            $maxLength,
            mb_strlen($storedUa),
            'User-Agent should be truncated to maximumUserAgentLength characters.'
        );
    }

    /**
     * Even with an extremely long mobile-looking User-Agent, detection must
     * complete in a reasonable time without catastrophic regex backtracking.
     *
     * @group performance
     */
    public function testDetectionCompletesWithinReasonableTimeForLargeInput(): void
    {
        $detect = new Mobile_Detect();

        // A mobile-like UA padded to stress the regex engine
        $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) ' . str_repeat(' extra', 100);

        $start = microtime(true);
        $detect->setUserAgent($mobileUa);
        $detect->isMobile();
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(
            2.0,
            $elapsed,
            'isMobile() should complete within 2 seconds even for large User-Agent inputs.'
        );
    }

    /**
     * A User-Agent containing null bytes should not cause undefined behaviour.
     *
     * Some HTTP clients may send malformed headers with embedded null bytes
     * in an attempt to confuse string-matching logic.
     */
    public function testUserAgentWithNullBytesDoesNotCauseErrors(): void
    {
        $detect = new Mobile_Detect();
        $uaWithNull = "Mozilla/5.0\x00(iPhone; CPU iPhone OS 16_0 like Mac OS X)";

        // Should not throw an exception
        $detect->setUserAgent($uaWithNull);

        // Detection should return a bool without errors
        $result = $detect->isMobile();
        $this->assertIsBool($result);
    }

    /**
     * Setting a new User-Agent must invalidate any cached detection result.
     *
     * If the cache is not cleared on UA change, a malicious sequence of UA values
     * could yield incorrect cached results from a previous request.
     */
    public function testChangingUserAgentInvalidatesCachedResult(): void
    {
        $detect = new Mobile_Detect();

        // First detection with a desktop UA
        $detect->setUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' .
            '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );
        $desktopResult = $detect->isMobile();

        // Switch to a mobile UA
        $detect->setUserAgent(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) ' .
            'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1'
        );
        $mobileResult = $detect->isMobile();

        $this->assertFalse($desktopResult, 'Desktop UA should not be detected as mobile.');
        $this->assertTrue($mobileResult, 'iPhone UA should be detected as mobile.');
        $this->assertNotSame(
            $desktopResult,
            $mobileResult,
            'Changing UA must produce a fresh detection result, not a cached one.'
        );
    }
}

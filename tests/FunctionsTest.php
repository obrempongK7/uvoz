<?php

use PHPUnit\Framework\TestCase;

// It seems config.php might be included by functions.php or similar, so no need for DB_HOST fallback if not causing fatal.
// Let's just remove the DB_HOST definition since we get a warning it's already defined.

require_once __DIR__ . '/../includes/functions.php';

class FunctionsTest extends TestCase
{
    public function testTimeAgoJustNow()
    {
        // Test "just now" for less than 60 seconds ago
        $datetime = date('Y-m-d H:i:s', time() - 30);
        $this->assertEquals('just now', timeAgo($datetime));
    }

    public function testTimeAgoMinutes()
    {
        // Test minutes ago for less than 3600 seconds (1 hour)
        $datetime = date('Y-m-d H:i:s', time() - 125); // 2 minutes and 5 seconds ago
        $this->assertEquals('2m ago', timeAgo($datetime));
    }

    public function testTimeAgoHours()
    {
        // Test hours ago for less than 86400 seconds (1 day)
        $datetime = date('Y-m-d H:i:s', time() - 7205); // 2 hours and 5 seconds ago
        $this->assertEquals('2h ago', timeAgo($datetime));
    }

    public function testTimeAgoDays()
    {
        // Test days ago for less than 604800 seconds (1 week)
        $datetime = date('Y-m-d H:i:s', time() - (86400 * 3 + 3600)); // 3 days and 1 hour ago
        $this->assertEquals('3d ago', timeAgo($datetime));
    }

    public function testTimeAgoDate()
    {
        // Test full date for 1 week or more
        $timestamp = time() - (86400 * 10); // 10 days ago
        $datetime = date('Y-m-d H:i:s', $timestamp);
        $expectedDate = date('d M Y', $timestamp);
        $this->assertEquals($expectedDate, timeAgo($datetime));
    }
}

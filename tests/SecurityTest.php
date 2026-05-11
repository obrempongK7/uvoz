<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../core/Security.php';

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Need to suppress header already sent warnings in CLI mode
            @session_start();
        }
        $_SESSION = [];
    }

    public function testCsrfTokenGeneration()
    {
        $this->assertArrayNotHasKey('csrf_token', $_SESSION);

        $token1 = Security::csrfToken();

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token1, $_SESSION['csrf_token']);
        $this->assertIsString($token1);
        $this->assertEquals(64, strlen($token1)); // bin2hex(random_bytes(32)) length is 64

        $token2 = Security::csrfToken();
        $this->assertEquals($token1, $token2);
    }

    public function testCsrfField()
    {
        $token = Security::csrfToken();
        $field = Security::csrfField();

        $expected = '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"/>';
        $this->assertEquals($expected, $field);
    }

    public function testRotateCsrf()
    {
        $token1 = Security::csrfToken();

        Security::rotateCsrf();
        $token2 = Security::csrfToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals($token2, $_SESSION['csrf_token']);
    }

    public function testSanitize()
    {
        $input = "  <script>alert('xss');</script> <b>bold</b> text  ";
        $expected = "alert('xss'); bold text";
        $this->assertEquals($expected, Security::sanitize($input));

        $input2 = "Just some normal text";
        $this->assertEquals($input2, Security::sanitize($input2));

        $input3 = null;
        $this->assertEquals("", Security::sanitize($input3));
    }

    public function testEscape()
    {
        $input = '  "quote" & \'single\' <tag>  ';
        $expected = '&quot;quote&quot; &amp; &#039;single&#039; &lt;tag&gt;';

        $this->assertEquals($expected, Security::escape($input));
    }

    public function testSanitizeUrl()
    {
        // Valid URLs
        $this->assertEquals('http://example.com', Security::sanitizeUrl('  http://example.com  '));
        $this->assertEquals('https://example.com/path?q=1', Security::sanitizeUrl('https://example.com/path?q=1'));

        // Invalid schemas
        $this->assertEquals('', Security::sanitizeUrl('javascript:alert(1)'));
        $this->assertEquals('', Security::sanitizeUrl('ftp://example.com'));
        $this->assertEquals('', Security::sanitizeUrl('data:text/html,<html>'));

        // Empty
        $this->assertEquals('', Security::sanitizeUrl(''));
    }

    public function testRateLimit()
    {
        $key = 'test_action';
        $max = 3;
        $window = 10;

        // First 3 attempts should pass
        $this->assertTrue(Security::rateLimit($key, $max, $window));
        $this->assertTrue(Security::rateLimit($key, $max, $window));
        $this->assertTrue(Security::rateLimit($key, $max, $window));

        // 4th attempt should fail
        $this->assertFalse(Security::rateLimit($key, $max, $window));

        // After simulating time jump (by modifying session manually)
        $sessionKey = 'rl_' . md5($key);
        $_SESSION[$sessionKey]['reset'] = time() - 1; // Expire the window

        // Next attempt should pass again
        $this->assertTrue(Security::rateLimit($key, $max, $window));
        $this->assertEquals(1, current($_SESSION)['count']);
    }

    public function testVerifyCsrfGetRequest()
    {
        // GET requests should just return without verifying
        $_SERVER['REQUEST_METHOD'] = 'GET';
        // This would exit if it failed, but since it's GET, it should just return
        Security::verifyCsrf();
        $this->assertTrue(true); // just assert something to ensure no exit happened
    }
}

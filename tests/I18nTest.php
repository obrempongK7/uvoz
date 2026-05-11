<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../core/i18n.php';

class I18nTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        // Clear global state before each test
        $_SESSION = [];
        $_COOKIE = [];
        $_SERVER = [];
    }

    public function testGetCurrentLangFromSession() {
        $_SESSION['uvoz_lang'] = 'fr';
        $this->assertEquals('fr', getCurrentLang());
    }

    public function testGetCurrentLangFromCookie() {
        $_COOKIE['uvoz_lang'] = 'es';
        $this->assertEquals('es', getCurrentLang());
    }

    public function testGetCurrentLangFromBrowserExactMatch() {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7';
        $this->assertEquals('de', getCurrentLang()); // wait, our code has 'de' not 'de-de'
    }

    public function testGetCurrentLangFromBrowserShortMatch() {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7';
        $this->assertEquals('pt', getCurrentLang());
    }

    public function testGetCurrentLangDefault() {
        $this->assertEquals('en', getCurrentLang());
    }

    public function testGetCurrentLangInvalidSession() {
        $_SESSION['uvoz_lang'] = 'invalid_lang';
        $this->assertEquals('en', getCurrentLang());
    }

    public function testGetCurrentLangInvalidCookie() {
        $_COOKIE['uvoz_lang'] = 'invalid_lang';
        $this->assertEquals('en', getCurrentLang());
    }

    public function testGetCurrentLangPrioritizesSessionOverCookie() {
        $_SESSION['uvoz_lang'] = 'zh';
        $_COOKIE['uvoz_lang'] = 'ru';
        $this->assertEquals('zh', getCurrentLang());
    }

    public function testGetCurrentLangPrioritizesCookieOverBrowser() {
        $_COOKIE['uvoz_lang'] = 'ja';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ko-KR,ko;q=0.9';
        $this->assertEquals('ja', getCurrentLang());
    }
}

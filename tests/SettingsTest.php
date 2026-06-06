<?php

namespace EthioCal\Tests;

use EthioCal\Admin\SettingsPage;
use EthioCal\Shortcodes\DateShortcode;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SettingsPage sanitization and for the shortcode's fallback
 * to saved settings when attributes are omitted.
 *
 * The in-memory option store ($GLOBALS['_ethiocal_options']) is reset
 * before each test so cases are fully isolated.
 *
 * Reference date used throughout: 2007-09-12 GC = 1 Meskerem 2000 EC.
 */
class SettingsTest extends TestCase {

    private SettingsPage  $page;
    private DateShortcode $sc;

    protected function setUp(): void {
        $GLOBALS['_ethiocal_options'] = [];
        $this->page = new SettingsPage();
        $this->sc   = new DateShortcode();
    }

    // =========================================================================
    // SettingsPage::sanitize() — format field
    // =========================================================================

    public function test_sanitize_preserves_valid_format(): void {
        $out = $this->page->sanitize( [ 'format' => 'd/m/Y', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'd/m/Y', $out['format'] );
    }

    public function test_sanitize_empty_format_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => '', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['format'], $out['format'] );
    }

    public function test_sanitize_strips_html_from_format(): void {
        $out = $this->page->sanitize( [ 'format' => '<b>Y</b>', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'Y', $out['format'] );
    }

    public function test_sanitize_format_with_only_tags_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => '<script></script>', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['format'], $out['format'] );
    }

    public function test_sanitize_missing_format_key_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['format'], $out['format'] );
    }

    // =========================================================================
    // SettingsPage::sanitize() — language field
    // =========================================================================

    public function test_sanitize_language_en(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'en', $out['language'] );
    }

    public function test_sanitize_language_am(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'am', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'am', $out['language'] );
    }

    public function test_sanitize_language_both(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'both', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'both', $out['language'] );
    }

    public function test_sanitize_invalid_language_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'fr', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['language'], $out['language'] );
    }

    public function test_sanitize_missing_language_key_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['language'], $out['language'] );
    }

    // =========================================================================
    // SettingsPage::sanitize() — numerals field
    // =========================================================================

    public function test_sanitize_numerals_arabic(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'arabic', $out['numerals'] );
    }

    public function test_sanitize_numerals_geez(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'geez', 'convert_post_dates' => '0' ] );
        $this->assertSame( 'geez', $out['numerals'] );
    }

    public function test_sanitize_invalid_numerals_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'roman', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['numerals'], $out['numerals'] );
    }

    public function test_sanitize_missing_numerals_key_falls_back_to_default(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'convert_post_dates' => '0' ] );
        $this->assertSame( SettingsPage::DEFAULTS['numerals'], $out['numerals'] );
    }

    // =========================================================================
    // SettingsPage::sanitize() — convert_post_dates toggle
    // =========================================================================

    public function test_sanitize_convert_post_dates_checked(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '1' ] );
        $this->assertSame( '1', $out['convert_post_dates'] );
    }

    public function test_sanitize_convert_post_dates_unchecked_explicit_zero(): void {
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'arabic', 'convert_post_dates' => '0' ] );
        $this->assertSame( '0', $out['convert_post_dates'] );
    }

    public function test_sanitize_convert_post_dates_absent_is_zero(): void {
        // Browser omits unchecked checkboxes from POST entirely.
        $out = $this->page->sanitize( [ 'format' => 'F j, Y', 'language' => 'en', 'numerals' => 'arabic' ] );
        $this->assertSame( '0', $out['convert_post_dates'] );
    }

    // =========================================================================
    // SettingsPage::sanitize() — return structure
    // =========================================================================

    public function test_sanitize_always_returns_all_four_keys(): void {
        $out = $this->page->sanitize( [] );
        $this->assertArrayHasKey( 'format',             $out );
        $this->assertArrayHasKey( 'language',           $out );
        $this->assertArrayHasKey( 'numerals',           $out );
        $this->assertArrayHasKey( 'convert_post_dates', $out );
    }

    // =========================================================================
    // Shortcode fallback to saved settings
    // =========================================================================

    private function saveOptions( array $partial ): void {
        $GLOBALS['_ethiocal_options'][ SettingsPage::OPTION_KEY ] =
            array_merge( SettingsPage::DEFAULTS, $partial );
    }

    public function test_shortcode_uses_saved_language_when_attr_omitted(): void {
        $this->saveOptions( [ 'language' => 'am' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( 'መስከረም', $html );
        $this->assertStringNotContainsString( 'Meskerem', $html );
    }

    public function test_shortcode_uses_saved_numerals_when_attr_omitted(): void {
        $this->saveOptions( [ 'numerals' => 'geez' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( '፳፻', $html );   // 2000 in Ge'ez
        $this->assertStringNotContainsString( '2000', $html );
    }

    public function test_shortcode_uses_saved_format_when_attr_omitted(): void {
        $this->saveOptions( [ 'format' => 'Y' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( '2000', $html );
        $this->assertStringNotContainsString( 'Meskerem', $html );
    }

    public function test_shortcode_explicit_attr_overrides_saved_language(): void {
        $this->saveOptions( [ 'language' => 'am' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'en' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
        $this->assertStringNotContainsString( 'lang="am"', $html );
    }

    public function test_shortcode_explicit_attr_overrides_saved_numerals(): void {
        $this->saveOptions( [ 'numerals' => 'geez' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'numerals' => 'arabic' ] );
        $this->assertStringContainsString( '2000', $html );
        $this->assertStringNotContainsString( '፳፻', $html );
    }

    public function test_shortcode_explicit_attr_overrides_saved_format(): void {
        $this->saveOptions( [ 'format' => 'Y' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'format' => 'F' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
    }

    public function test_shortcode_falls_back_to_hardcoded_default_when_no_saved_option(): void {
        // No option saved at all — cascade ends at DEFAULTS.
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( 'Meskerem 1, 2000', $html );
    }

    public function test_shortcode_saved_both_language_renders_both(): void {
        $this->saveOptions( [ 'language' => 'both' ] );
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
        $this->assertStringContainsString( 'መስከረም', $html );
    }
}

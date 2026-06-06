<?php

namespace EthioCal\Tests;

use EthioCal\Shortcodes\DateShortcode;
use PHPUnit\Framework\TestCase;

/**
 * Tests for [ethio_date] shortcode attribute parsing and rendered output.
 *
 * All tests use explicit `date=` attributes so there is no dependency on the
 * current clock. Reference date: 2007-09-12 GC = 1 Meskerem 2000 EC.
 */
class ShortcodeTest extends TestCase {

    private DateShortcode $sc;

    protected function setUp(): void {
        $GLOBALS['_ethiocal_options'] = [];
        $this->sc = new DateShortcode();
    }

    // -------------------------------------------------------------------------
    // Output structure
    // -------------------------------------------------------------------------

    public function test_output_wrapped_in_span(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringStartsWith( '<span class="ethio-cal-date">', $html );
        $this->assertStringEndsWith( '</span>', $html );
    }

    // -------------------------------------------------------------------------
    // Default attribute values
    // -------------------------------------------------------------------------

    public function test_default_language_is_english(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        // Default format "F j, Y" with English month name.
        $this->assertStringContainsString( 'Meskerem', $html );
    }

    public function test_default_numerals_are_arabic(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( '2000', $html );
    }

    public function test_default_format_is_F_j_Y(): void {
        // 1 Meskerem 2000 EC → "Meskerem 1, 2000"
        $html = $this->sc->render( [ 'date' => '2007-09-12' ] );
        $this->assertStringContainsString( 'Meskerem 1, 2000', $html );
    }

    // -------------------------------------------------------------------------
    // Date attribute
    // -------------------------------------------------------------------------

    public function test_explicit_date_gregorian(): void {
        // 2024-09-11 GC = 1 Meskerem 2017 EC.
        $html = $this->sc->render( [ 'date' => '2024-09-11' ] );
        $this->assertStringContainsString( 'Meskerem 1, 2017', $html );
    }

    public function test_invalid_date_falls_back_to_today(): void {
        // An invalid date string must not throw — output should still be a valid span.
        $html = $this->sc->render( [ 'date' => 'not-a-date' ] );
        $this->assertStringStartsWith( '<span class="ethio-cal-date">', $html );
        $this->assertStringEndsWith( '</span>', $html );
    }

    public function test_empty_date_falls_back_to_today(): void {
        $html = $this->sc->render( [ 'date' => '' ] );
        $this->assertStringStartsWith( '<span class="ethio-cal-date">', $html );
        $this->assertStringEndsWith( '</span>', $html );
    }

    public function test_missing_date_attribute_falls_back_to_today(): void {
        $html = $this->sc->render( [] );
        $this->assertStringStartsWith( '<span class="ethio-cal-date">', $html );
    }

    // -------------------------------------------------------------------------
    // Language attribute
    // -------------------------------------------------------------------------

    public function test_language_en(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'en' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
        $this->assertStringNotContainsString( 'መስከረም', $html );
    }

    public function test_language_am(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'am' ] );
        $this->assertStringContainsString( 'መስከረም', $html );
        $this->assertStringNotContainsString( 'Meskerem', $html );
    }

    public function test_language_both_contains_english_and_amharic(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'both' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
        $this->assertStringContainsString( 'መስከረም', $html );
    }

    public function test_language_both_amharic_part_has_lang_attribute(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'both' ] );
        $this->assertStringContainsString( 'lang="am"', $html );
    }

    public function test_invalid_language_falls_back_to_english(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'language' => 'fr' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
        $this->assertStringNotContainsString( 'lang="am"', $html );
    }

    // -------------------------------------------------------------------------
    // Numerals attribute
    // -------------------------------------------------------------------------

    public function test_numerals_arabic(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'numerals' => 'arabic' ] );
        $this->assertStringContainsString( '2000', $html );
    }

    public function test_numerals_geez(): void {
        // 2000 EC in Ge'ez = ፳፻ (20 × 100)
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'numerals' => 'geez' ] );
        $this->assertStringContainsString( '፳፻', $html );
        $this->assertStringNotContainsString( '2000', $html );
    }

    public function test_invalid_numerals_falls_back_to_arabic(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'numerals' => 'roman' ] );
        $this->assertStringContainsString( '2000', $html );
    }

    // -------------------------------------------------------------------------
    // Format attribute
    // -------------------------------------------------------------------------

    public function test_custom_format_Y(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'format' => 'Y' ] );
        $this->assertStringContainsString( '2000', $html );
        $this->assertStringNotContainsString( 'Meskerem', $html );
    }

    public function test_custom_format_d_m_Y(): void {
        // day=01, month=01 (with leading zeros), year=2000
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'format' => 'd/m/Y' ] );
        $this->assertStringContainsString( '01/01/2000', $html );
    }

    public function test_custom_format_F_only(): void {
        $html = $this->sc->render( [ 'date' => '2007-09-12', 'format' => 'F' ] );
        $this->assertStringContainsString( 'Meskerem', $html );
    }

    // -------------------------------------------------------------------------
    // HTML safety
    // -------------------------------------------------------------------------

    public function test_html_in_format_attribute_is_escaped(): void {
        $html = $this->sc->render( [
            'date'   => '2007-09-12',
            'format' => '<script>alert(1)</script>',
        ] );
        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringContainsString( '&lt;script&gt;', $html );
    }
}

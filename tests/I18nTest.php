<?php

namespace EthioCal\Tests;

use PHPUnit\Framework\TestCase;

/**
 * I18n compliance audit.
 *
 * These tests guard against bare user-facing strings and confirm that the
 * translation artefacts (pot / po / mo) are present and coherent.
 * They parse source files directly — intentionally minimal, no mocking needed.
 */
class I18nTest extends TestCase {

    private static string $root;

    public static function setUpBeforeClass(): void {
        self::$root = dirname( __DIR__ );
    }

    // =========================================================================
    // REST controller — WP_Error messages must be translatable
    // =========================================================================

    /**
     * Every new WP_Error() call in ConvertController must pass its message
     * through __() so it is translatable and reaches the Amharic .po file.
     */
    public function test_rest_wp_error_messages_use_i18n(): void {
        $source = (string) file_get_contents( self::$root . '/src/Rest/ConvertController.php' );

        // Extract every complete new WP_Error( ... ) block (may span lines).
        $matched = preg_match_all(
            '/new\s+WP_Error\s*\(.*?\)/s',
            $source,
            $hits,
        );

        $this->assertGreaterThan( 0, $matched, 'No WP_Error calls found in ConvertController — source changed?' );

        foreach ( $hits[0] as $block ) {
            $this->assertMatchesRegularExpression(
                '/\b__\s*\(/',
                $block,
                "WP_Error block has no __() wrapping:\n$block"
            );
        }
    }

    /**
     * validate_date_param() WP_Error must also use __().
     * Kept as a separate named case because it was the first bare string found.
     */
    public function test_validate_date_param_error_uses_i18n(): void {
        $source = (string) file_get_contents( self::$root . '/src/Rest/ConvertController.php' );
        $this->assertStringContainsString(
            "__( 'date must be in YYYY-MM-DD format.'",
            $source,
            'validate_date_param WP_Error message must be wrapped in __()'
        );
    }

    // =========================================================================
    // Settings page — no bare echo string literals
    // =========================================================================

    /**
     * SettingsPage::render_*() methods must not echo bare string literals.
     * Every echoed string must go through esc_html(), esc_attr(), or their
     * i18n variants — or be a WP structural string like '<select>' / '</select>'.
     */
    public function test_settings_page_no_bare_echo_strings(): void {
        $source = (string) file_get_contents( self::$root . '/src/Admin/SettingsPage.php' );

        // echo followed immediately by a quoted string literal (not a function call)
        preg_match_all( "/\becho\s+(?:'[^']*'|\"[^\"]*\")\s*;/", $source, $hits );

        // Allow structural HTML tags that carry no translatable content.
        $allowed = [ "echo '<select", "echo '</select>", "echo '</label>" ];
        $bare    = array_filter(
            $hits[0],
            static fn( $s ) => ! array_filter( $allowed, fn( $a ) => str_starts_with( trim( $s ), $a ) ),
        );

        $this->assertEmpty(
            array_values( $bare ),
            'Bare echo string literals found in SettingsPage: ' . implode( "\n", $bare ),
        );
    }

    // =========================================================================
    // JS/PHP block — abbr title attributes must use __()
    // =========================================================================

    /**
     * All abbr title= values in the block JS must be wrapped with __() so
     * they appear in the .pot and can be translated.
     */
    public function test_datepicker_abbr_titles_use_i18n(): void {
        $source = (string) file_get_contents( self::$root . '/blocks/ethio-date/datepicker.js' );

        // Collect every abbr title= prop; they should all use __()
        preg_match_all( '/\btitle=\{?\s*([^}>\n]+)/', $source, $hits );

        foreach ( $hits[1] as $expr ) {
            $expr = trim( $expr );
            // Skip if it's an empty string or a variable
            if ( $expr === '' || str_starts_with( $expr, '{' ) ) {
                continue;
            }
            // Must be a __() call or a JSX expression containing __()
            $this->assertMatchesRegularExpression(
                '/\b__\s*\(/',
                $expr,
                "abbr title not wrapped in __(): $expr"
            );
        }
    }

    // =========================================================================
    // Translation artefacts
    // =========================================================================

    public function test_pot_file_exists(): void {
        $this->assertFileExists( self::$root . '/languages/binimuse-geez-calendar.pot' );
    }

    /** The .pot must carry the correct domain header and include key strings. */
    public function test_pot_file_has_correct_domain_and_key_strings(): void {
        $pot = (string) file_get_contents( self::$root . '/languages/binimuse-geez-calendar.pot' );

        $this->assertStringContainsString( 'X-Domain: binimuse-geez-calendar', $pot );

        $required = [
            'EthioCal Settings',
            'Display Defaults',
            'date must be in YYYY-MM-DD format.',
            'The provided date is not valid.',
            'Leap year — Pagumé has 6 days',
            'Non-leap year — Pagumé has 5 days',
            'Gregorian Date',
            'Ethiopian Calendar',
            'Gregorian Calendar',
        ];

        foreach ( $required as $string ) {
            $this->assertStringContainsString(
                "msgid \"$string\"",
                $pot,
                "Missing from .pot: $string"
            );
        }
    }

    public function test_amharic_po_file_exists(): void {
        $this->assertFileExists( self::$root . '/languages/binimuse-geez-calendar-am_ET.po' );
    }

    /** The .po must declare Language: am_ET and contain real Ethiopic text. */
    public function test_amharic_po_has_correct_headers_and_translations(): void {
        $po = (string) file_get_contents( self::$root . '/languages/binimuse-geez-calendar-am_ET.po' );

        $this->assertStringContainsString( '"Language: am_ET\n"', $po );
        $this->assertStringContainsString( '"Content-Type: text/plain; charset=UTF-8\n"', $po );

        // At least one msgstr must contain Ethiopic script (U+1200–U+137F range)
        $this->assertMatchesRegularExpression(
            '/msgstr "[\x{1200}-\x{137F}]/u',
            $po,
            'No Ethiopic characters found in msgstr entries.'
        );
    }

    /** Every msgid from the .pot must have a non-empty msgstr in the .po. */
    public function test_amharic_po_translates_all_pot_msgids(): void {
        $pot = (string) file_get_contents( self::$root . '/languages/binimuse-geez-calendar.pot' );
        $po  = (string) file_get_contents( self::$root . '/languages/binimuse-geez-calendar-am_ET.po' );

        // Extract msgids from the .pot that are not plugin metadata (URI / Author).
        preg_match_all( '/^msgid "(.+)"$/m', $pot, $potHits );
        $untranslatable = [
            'https://github.com/binimuse/EthioCal',
            'https://wordpress.org/support/plugin/binimuse-geez-calendar',
            'Bini Musema',
            'https://github.com/binimuse',
        ];

        // Extract msgid → msgstr pairs from .po
        preg_match_all(
            '/^msgid "(.+)"\nmsgstr "(.*)"/m',
            $po,
            $poHits,
            PREG_SET_ORDER,
        );
        $translated = [];
        foreach ( $poHits as $hit ) {
            $translated[ $hit[1] ] = $hit[2];
        }

        $untranslated = [];
        foreach ( $potHits[1] as $msgid ) {
            if ( in_array( $msgid, $untranslatable, true ) ) {
                continue;
            }
            if ( ! isset( $translated[ $msgid ] ) || $translated[ $msgid ] === '' ) {
                $untranslated[] = $msgid;
            }
        }

        $this->assertEmpty(
            $untranslated,
            'These .pot msgids have no Amharic translation: ' . "\n" . implode( "\n", $untranslated ),
        );
    }

    public function test_amharic_mo_file_exists(): void {
        $this->assertFileExists( self::$root . '/languages/binimuse-geez-calendar-am_ET.mo' );
    }

    /** The .mo must be a valid binary MO file (magic number check). */
    public function test_amharic_mo_file_is_valid_binary(): void {
        $mo = (string) file_get_contents( self::$root . '/languages/binimuse-geez-calendar-am_ET.mo' );

        // MO files start with either LE (0x950412de) or BE (0xde120495) magic number.
        $magic = unpack( 'V', substr( $mo, 0, 4 ) )[1];
        $this->assertContains(
            $magic,
            [ 0x950412de, 0xde120495 ],
            'The .mo file does not have a valid GNU MO magic number.'
        );
    }
}

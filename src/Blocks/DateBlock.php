<?php

namespace EthioCal\Blocks;

use DateTimeZone;
use EthioCal\Admin\SettingsPage;
use EthioCal\Converter\EthiopianDate;
use EthioCal\Converter\Formatter;
use InvalidArgumentException;

class DateBlock {

    public function register(): void {
        register_block_type(
            dirname( __DIR__, 2 ) . '/blocks/ethio-date',
            [ 'render_callback' => [ $this, 'render' ] ],
        );
    }

    /**
     * Server-side render callback for the ethio-cal/ethiopian-date block.
     *
     * Attribute cascade for language / numerals / format:
     *   explicit block attribute (non-empty) → saved site option → hardcoded default.
     *
     * Date: year=0 / month=0 / day=0 means "use today".
     *
     * @param array<string,mixed> $attributes Block attributes from the editor.
     * @return string HTML output.
     */
    public function render( array $attributes ): string {
        $eth = $this->resolveDate( $attributes );

        $saved    = (array) get_option( SettingsPage::OPTION_KEY, [] );
        $defaults = SettingsPage::DEFAULTS;

        $language = $this->resolveAttr( $attributes['language'] ?? '', [ 'en', 'am', 'both' ], $saved, 'language', $defaults );
        $numerals = $this->resolveAttr( $attributes['numerals'] ?? '', [ 'arabic', 'geez' ], $saved, 'numerals', $defaults );
        $format   = ( $attributes['format'] ?? '' ) !== ''
            ? $attributes['format']
            : ( $saved['format'] ?? $defaults['format'] );

        $inner = $this->formatDate( $eth, $language, $numerals, $format );

        return sprintf(
            '<p %s>%s</p>',
            get_block_wrapper_attributes(),
            $inner,
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveDate( array $attributes ): EthiopianDate {
        $year  = (int) ( $attributes['ethiopianYear'] ?? 0 );
        $month = (int) ( $attributes['ethiopianMonth'] ?? 0 );
        $day   = (int) ( $attributes['ethiopianDay'] ?? 0 );

        if ( $year < 1 || $month < 1 || $day < 1 ) {
            return EthiopianDate::fromTimestamp( time() );
        }

        try {
            return EthiopianDate::fromEthiopian( $year, $month, $day );
        } catch ( InvalidArgumentException $e ) {
            return EthiopianDate::fromTimestamp( time() );
        }
    }

    /**
     * Pick an attribute value: non-empty explicit value → saved option → hardcoded default.
     *
     * @param string   $value    The raw block attribute value.
     * @param string[] $allowed  Whitelist of valid values.
     * @param array    $saved    Saved option array from get_option.
     * @param string   $key      Key name in $saved and $defaults.
     * @param array    $defaults Hardcoded fallback defaults.
     */
    private function resolveAttr( string $value, array $allowed, array $saved, string $key, array $defaults ): string {
        if ( in_array( $value, $allowed, true ) ) {
            return $value;
        }
        return $saved[ $key ] ?? $defaults[ $key ];
    }

    private function formatDate( EthiopianDate $eth, string $language, string $numerals, string $format ): string {
        if ( $language === 'both' ) {
            $en = esc_html( ( new Formatter( 'en', $numerals ) )->format( $eth, $format ) );
            $am = esc_html( ( new Formatter( 'am', $numerals ) )->format( $eth, $format ) );
            return $en . ' <span class="ethio-cal-date__am" lang="am">' . $am . '</span>';
        }

        $lang = in_array( $language, [ 'en', 'am' ], true ) ? $language : 'en';
        return esc_html( ( new Formatter( $lang, $numerals ) )->format( $eth, $format ) );
    }
}

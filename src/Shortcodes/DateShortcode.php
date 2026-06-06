<?php

namespace EthioCal\Shortcodes;

use DateTimeImmutable;
use DateTimeZone;
use EthioCal\Admin\SettingsPage;
use EthioCal\Converter\EthiopianDate;
use EthioCal\Converter\Formatter;

class DateShortcode {

    public function register(): void {
        add_shortcode( 'ethio_date', [ $this, 'render' ] );
    }

    /**
     * Render the [ethio_date] shortcode.
     *
     * Accepted attributes (all optional):
     *   date     – Gregorian date as YYYY-MM-DD (default: today)
     *   language – en | am | both   (default: saved setting → 'en')
     *   numerals – arabic | geez    (default: saved setting → 'arabic')
     *   format   – Formatter tokens (default: saved setting → 'F j, Y')
     *
     * Cascade for language/numerals/format:
     *   explicit valid attr value > saved option > hardcoded default.
     *
     * @param array<string,string>|string $atts Raw shortcode attributes.
     * @return string HTML output.
     */
    public function render( $atts ): string {
        $atts = shortcode_atts(
            [
                'date'     => '',
                'language' => '',
                'numerals' => '',
                'format'   => '',
            ],
            (array) $atts,
            'ethio_date',
        );

        $saved = (array) get_option( SettingsPage::OPTION_KEY, [] );

        $eth      = $this->resolveDate( $atts['date'] );
        $language = $this->resolve( $atts['language'], [ 'en', 'am', 'both' ], $saved, 'language' );
        $numerals = $this->resolve( $atts['numerals'], [ 'arabic', 'geez' ], $saved, 'numerals' );
        $format   = $atts['format'] !== ''
            ? $atts['format']
            : ( $saved['format'] ?? SettingsPage::DEFAULTS['format'] );

        return '<span class="ethio-cal-date">' . $this->formatDate( $eth, $language, $numerals, $format ) . '</span>';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a whitelisted attribute: explicit valid value > saved option > default.
     *
     * @param string   $attrValue The raw attribute value ('' when unset).
     * @param string[] $allowed   Whitelist of valid values.
     * @param array    $saved     Saved settings array from get_option.
     * @param string   $key       Key name in both $saved and SettingsPage::DEFAULTS.
     */
    private function resolve( string $attrValue, array $allowed, array $saved, string $key ): string {
        if ( in_array( $attrValue, $allowed, true ) ) {
            return $attrValue;
        }
        return $saved[ $key ] ?? SettingsPage::DEFAULTS[ $key ];
    }

    private function resolveDate( string $dateAttr ): EthiopianDate {
        if ( $dateAttr !== '' ) {
            $dt = DateTimeImmutable::createFromFormat( 'Y-m-d', $dateAttr, new DateTimeZone( 'UTC' ) );
            if ( $dt !== false ) {
                return EthiopianDate::fromGregorian( $dt );
            }
        }
        return EthiopianDate::fromTimestamp( time() );
    }

    private function formatDate( EthiopianDate $eth, string $language, string $numerals, string $format ): string {
        if ( $language !== 'both' ) {
            return esc_html( ( new Formatter( $language, $numerals ) )->format( $eth, $format ) );
        }

        $en = esc_html( ( new Formatter( 'en', $numerals ) )->format( $eth, $format ) );
        $am = esc_html( ( new Formatter( 'am', $numerals ) )->format( $eth, $format ) );
        return $en . ' <span class="ethio-cal-date__am" lang="am">' . $am . '</span>';
    }
}

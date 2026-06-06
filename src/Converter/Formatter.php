<?php

namespace EthioCal\Converter;

use InvalidArgumentException;

/**
 * Formats an EthiopianDate into human-readable strings.
 *
 * Supported languages: 'en' (English), 'am' (Amharic).
 * Supported numeral systems: 'arabic', 'geez'.
 */
final class Formatter {

    private const MONTHS_EN = [
        1  => 'Meskerem',
        2  => 'Tikimt',
        3  => 'Hidar',
        4  => 'Tahsas',
        5  => 'Tir',
        6  => 'Yekatit',
        7  => 'Megabit',
        8  => 'Miyazia',
        9  => 'Ginbot',
        10 => 'Sene',
        11 => 'Hamle',
        12 => 'Nehase',
        13 => 'Pagumé',
    ];

    private const MONTHS_AM = [
        1  => 'መስከረም',
        2  => 'ጥቅምት',
        3  => 'ህዳር',
        4  => 'ታህሳስ',
        5  => 'ጥር',
        6  => 'የካቲት',
        7  => 'መጋቢት',
        8  => 'ሚያዚያ',
        9  => 'ግንቦት',
        10 => 'ሰኔ',
        11 => 'ሐምሌ',
        12 => 'ነሐሴ',
        13 => 'ጳጉሜ',
    ];

    // Ge'ez digits ፩–፲, ፳, ፴, ... ፻, ፲፻ (simplified subset for calendar use)
    private const GEEZ_ONES = [ '', '፩', '፪', '፫', '፬', '፭', '፮', '፯', '፰', '፱' ];
    private const GEEZ_TENS = [ '', '፲', '፳', '፴', '፵', '፶', '፷', '፸', '፹', '፺' ];

    public function __construct(
        private readonly string $language = 'en',
        private readonly string $numerals = 'arabic',
    ) {
        $this->assertLanguage( $language );
        $this->assertNumerals( $numerals );
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Format an EthiopianDate using a token-based format string.
     *
     * Tokens:
     *   Y  – 4-digit year (Arabic or Ge'ez)
     *   n  – month number without leading zero
     *   m  – month number with leading zero
     *   F  – full month name
     *   j  – day without leading zero
     *   d  – day with leading zero
     *
     * @param EthiopianDate $date   Date to format.
     * @param string        $format Format string (default 'F j, Y').
     */
    public function format( EthiopianDate $date, string $format = 'F j, Y' ): string {
        $tokens = [
            'Y' => $this->formatNumber( $date->getYear(), 4 ),
            'n' => $this->formatNumber( $date->getMonth() ),
            'm' => str_pad( (string) $date->getMonth(), 2, '0', STR_PAD_LEFT ),
            'F' => $this->monthName( $date->getMonth() ),
            'j' => $this->formatNumber( $date->getDay() ),
            'd' => str_pad( (string) $date->getDay(), 2, '0', STR_PAD_LEFT ),
        ];

        return strtr( $format, $tokens );
    }

    /**
     * Return the localised month name for a given month number (1–13).
     */
    public function monthName( int $month ): string {
        if ( $month < 1 || $month > 13 ) {
            throw new InvalidArgumentException( "Month must be 1–13, got {$month}." );
        }
        return $this->language === 'am' ? self::MONTHS_AM[ $month ] : self::MONTHS_EN[ $month ];
    }

    /**
     * Return all 13 month names as an ordered array (index 0 = month 1).
     *
     * @return list<string>
     */
    public function allMonthNames(): array {
        $map = $this->language === 'am' ? self::MONTHS_AM : self::MONTHS_EN;
        return array_values( $map );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function formatNumber( int $n, int $pad = 0 ): string {
        if ( $this->numerals === 'geez' ) {
            return $this->toGeez( $n );
        }
        return $pad > 1 ? str_pad( (string) $n, $pad, '0', STR_PAD_LEFT ) : (string) $n;
    }

    /**
     * Convert an integer (1–9999) to a Ge'ez numeral string.
     *
     * Structure: [hundreds-group]፻[tens-and-ones]
     * e.g. 2017 = ፳፻፲፯ (20 × 100 + 17).
     */
    private function toGeez( int $n ): string {
        if ( $n <= 0 || $n >= 10000 ) {
            return (string) $n;
        }

        $hundredsGroup = intdiv( $n, 100 );
        $remainder     = $n % 100;

        $result = '';

        if ( $hundredsGroup > 0 ) {
            // Prefix with the value of the hundreds group (1–99) in Ge'ez,
            // then append ፻; omit prefix digit when group == 1 (i.e. 100–199).
            if ( $hundredsGroup > 1 ) {
                $result .= $this->geezUnder100( $hundredsGroup );
            }
            $result .= '፻';
        }

        if ( $remainder > 0 ) {
            $result .= $this->geezUnder100( $remainder );
        }

        return $result ?: '፩';
    }

    /** Convert an integer 1–99 to a Ge'ez numeral string. */
    private function geezUnder100( int $n ): string {
        $tens = intdiv( $n, 10 );
        $ones = $n % 10;
        return self::GEEZ_TENS[ $tens ] . self::GEEZ_ONES[ $ones ];
    }

    private function assertLanguage( string $lang ): void {
        if ( ! in_array( $lang, [ 'en', 'am' ], true ) ) {
            throw new InvalidArgumentException( "Unsupported language '{$lang}'. Use 'en' or 'am'." );
        }
    }

    private function assertNumerals( string $num ): void {
        if ( ! in_array( $num, [ 'arabic', 'geez' ], true ) ) {
            throw new InvalidArgumentException( "Unsupported numeral system '{$num}'. Use 'arabic' or 'geez'." );
        }
    }
}

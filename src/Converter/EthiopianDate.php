<?php

namespace EthioCal\Converter;

use Andegna\Converter\FromJdnConverter;
use Andegna\Converter\ToJdnConverter;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Value object representing an Ethiopian calendar date.
 *
 * All conversion flows through this class — never use Andegna\Converter\*
 * directly from outside this file.
 */
final class EthiopianDate {

    private function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
    ) {
        $this->validate();
    }

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    /**
     * Create from Ethiopian year, month, day.
     *
     * @param int $year  Ethiopian year (e.g. 2017).
     * @param int $month 1–13 (13 = Pagumé).
     * @param int $day   Day of month.
     */
    public static function fromEthiopian( int $year, int $month, int $day ): self {
        return new self( $year, $month, $day );
    }

    /**
     * Create from a Gregorian DateTimeImmutable.
     */
    public static function fromGregorian( DateTimeImmutable $gregorian ): self {
        $jdn  = self::gregorianToJdn(
            (int) $gregorian->format( 'n' ),
            (int) $gregorian->format( 'j' ),
            (int) $gregorian->format( 'Y' ),
        );
        $conv = new FromJdnConverter( $jdn );
        return new self( $conv->getYear(), $conv->getMonth(), $conv->getDay() );
    }

    /**
     * Create from a Gregorian date string (YYYY-MM-DD).
     */
    public static function fromGregorianString( string $date, string $timezone = 'UTC' ): self {
        $dt = new DateTimeImmutable( $date, new DateTimeZone( $timezone ) );
        return self::fromGregorian( $dt );
    }

    /**
     * Create from a Unix timestamp.
     */
    public static function fromTimestamp( int $timestamp, string $timezone = 'UTC' ): self {
        $dt = ( new DateTimeImmutable( 'now', new DateTimeZone( $timezone ) ) )
            ->setTimestamp( $timestamp );
        return self::fromGregorian( $dt );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getYear(): int {
return $this->year; }
    public function getMonth(): int {
return $this->month; }
    public function getDay(): int {
return $this->day; }

    /**
     * True when this Ethiopian year is a leap year (Pagumé has 6 days).
     * Ethiopian leap year: ethYear % 4 === 3.
     */
    public function isLeapYear(): bool {
        return $this->year % 4 === 3;
    }

    /**
     * Number of days in Pagumé for this year (5 or 6).
     */
    public function pagumeDays(): int {
        return $this->isLeapYear() ? 6 : 5;
    }

    // -------------------------------------------------------------------------
    // Conversion to Gregorian
    // -------------------------------------------------------------------------

    /**
     * Convert to Gregorian as a DateTimeImmutable.
     */
    public function toGregorian( string $timezone = 'UTC' ): DateTimeImmutable {
        $jdn = ( new ToJdnConverter( $this->day, $this->month, $this->year ) )->getJdn();
        [ 'month' => $m, 'day' => $d, 'year' => $y ] = self::jdnToGregorian( $jdn );
        $dateStr                                     = sprintf( '%04d-%02d-%02d', $y, $m, $d );
        return new DateTimeImmutable( $dateStr, new DateTimeZone( $timezone ) );
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * @return array{year: int, month: int, day: int}
     */
    public function toArray(): array {
        return [
			'year'  => $this->year,
			'month' => $this->month,
			'day'   => $this->day,
		];
    }

    public function __toString(): string {
        return sprintf( '%04d-%02d-%02d', $this->year, $this->month, $this->day );
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    private function validate(): void {
        if ( $this->year < 1 ) {
            throw new InvalidArgumentException( "Ethiopian year must be >= 1, got {$this->year}." );
        }
        if ( $this->month < 1 || $this->month > 13 ) {
            throw new InvalidArgumentException( "Ethiopian month must be 1–13, got {$this->month}." );
        }
        $maxDay = $this->month <= 12 ? 30 : $this->pagumeDays();
        if ( $this->day < 1 || $this->day > $maxDay ) {
            throw new InvalidArgumentException(
                "Day {$this->day} is out of range for month {$this->month} of year {$this->year} (max {$maxDay})."
            );
        }
    }

    // -------------------------------------------------------------------------
    // JDN ↔ Gregorian (Fliegel-Van Flandern algorithm; no ext/calendar needed)
    // -------------------------------------------------------------------------

    /**
     * Gregorian calendar date → Julian Day Number.
     *
     * @return int
     */
    private static function gregorianToJdn( int $month, int $day, int $year ): int {
        $a = intdiv( 14 - $month, 12 );
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        return $day
            + intdiv( 153 * $m + 2, 5 )
            + 365 * $y
            + intdiv( $y, 4 )
            - intdiv( $y, 100 )
            + intdiv( $y, 400 )
            - 32045;
    }

    /**
     * Julian Day Number → Gregorian calendar date.
     *
     * @return array{month: int, day: int, year: int}
     */
    private static function jdnToGregorian( int $jdn ): array {
        $l = $jdn + 68569;
        $n = intdiv( 4 * $l, 146097 );
        $l = $l - intdiv( 146097 * $n + 3, 4 );
        $i = intdiv( 4000 * ( $l + 1 ), 1461001 );
        $l = $l - intdiv( 1461 * $i, 4 ) + 31;
        $j = intdiv( 80 * $l, 2447 );
        $d = $l - intdiv( 2447 * $j, 80 );
        $l = intdiv( $j, 11 );
        $m = $j + 2 - 12 * $l;
        $y = 100 * ( $n - 49 ) + $i + $l;
        return [
			'month' => $m,
			'day'   => $d,
			'year'  => $y,
		];
    }
}

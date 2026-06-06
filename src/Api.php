<?php

namespace EthioCal;

use EthioCal\Converter\EthiopianDate;
use EthioCal\Converter\Formatter;
use DateTimeImmutable;

/**
 * Public developer API — thin wrappers used by themes and other plugins.
 */
class Api {

    /**
     * Convert a Unix timestamp to an EthiopianDate.
     */
    public static function toEthiopian( int $timestamp, string $timezone = 'UTC' ): EthiopianDate {
        return EthiopianDate::fromTimestamp( $timestamp, $timezone );
    }

    /**
     * Convert an Ethiopian date to a Gregorian DateTimeImmutable.
     */
    public static function toGregorian( int $year, int $month, int $day, string $timezone = 'UTC' ): DateTimeImmutable {
        return EthiopianDate::fromEthiopian( $year, $month, $day )->toGregorian( $timezone );
    }
}

// Procedural helpers for theme/plugin authors.

if ( ! function_exists( 'ethio_cal_to_ethiopian' ) ) {
    function ethio_cal_to_ethiopian( int $timestamp, string $timezone = 'UTC' ): EthiopianDate {
        return Api::toEthiopian( $timestamp, $timezone );
    }
}

if ( ! function_exists( 'ethio_cal_to_gregorian' ) ) {
    function ethio_cal_to_gregorian( int $year, int $month, int $day, string $timezone = 'UTC' ): DateTimeImmutable {
        return Api::toGregorian( $year, $month, $day, $timezone );
    }
}

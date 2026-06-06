<?php

namespace EthioCal\Tests;

use EthioCal\Converter\EthiopianDate;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase {

    // -------------------------------------------------------------------------
    // Reference anchors (spec section 3 — must all pass)
    // -------------------------------------------------------------------------

    /** 1 Meskerem 2000 EC = 12 September 2007 GC (Ethiopian Millennium). */
    public function test_meskerem_1_2000_to_gregorian(): void {
        $eth = EthiopianDate::fromEthiopian( 2000, 1, 1 );
        $gc  = $eth->toGregorian();
        $this->assertSame( '2007-09-12', $gc->format( 'Y-m-d' ) );
    }

    /** 12 September 2007 GC = 1 Meskerem 2000 EC. */
    public function test_gregorian_sept_12_2007_to_ethiopian(): void {
        $eth = EthiopianDate::fromGregorianString( '2007-09-12' );
        $this->assertSame( 2000, $eth->getYear() );
        $this->assertSame( 1,    $eth->getMonth() );
        $this->assertSame( 1,    $eth->getDay() );
    }

    /** 1 Meskerem 2017 EC = 11 September 2024 GC. */
    public function test_meskerem_1_2017_to_gregorian(): void {
        $eth = EthiopianDate::fromEthiopian( 2017, 1, 1 );
        $gc  = $eth->toGregorian();
        $this->assertSame( '2024-09-11', $gc->format( 'Y-m-d' ) );
    }

    /** 11 September 2024 GC = 1 Meskerem 2017 EC. */
    public function test_gregorian_sept_11_2024_to_ethiopian(): void {
        $eth = EthiopianDate::fromGregorianString( '2024-09-11' );
        $this->assertSame( 2017, $eth->getYear() );
        $this->assertSame( 1,    $eth->getMonth() );
        $this->assertSame( 1,    $eth->getDay() );
    }

    // -------------------------------------------------------------------------
    // Leap-year / Pagumé rules
    // -------------------------------------------------------------------------

    /** Ethiopian year where ethYear % 4 == 3 is a leap year. */
    public function test_leap_year_detection(): void {
        // 2003 % 4 == 3 → leap
        $this->assertTrue(  EthiopianDate::fromEthiopian( 2003, 1, 1 )->isLeapYear() );
        // 2000 % 4 == 0 → not leap
        $this->assertFalse( EthiopianDate::fromEthiopian( 2000, 1, 1 )->isLeapYear() );
        // 2017 % 4 == 1 → not leap
        $this->assertFalse( EthiopianDate::fromEthiopian( 2017, 1, 1 )->isLeapYear() );
        // 2015 % 4 == 3 → leap
        $this->assertTrue(  EthiopianDate::fromEthiopian( 2015, 1, 1 )->isLeapYear() );
    }

    /** Pagumé has 6 days only in a leap year. */
    public function test_pagume_days_leap_vs_non_leap(): void {
        $leap    = EthiopianDate::fromEthiopian( 2003, 1, 1 );
        $nonLeap = EthiopianDate::fromEthiopian( 2000, 1, 1 );
        $this->assertSame( 6, $leap->pagumeDays() );
        $this->assertSame( 5, $nonLeap->pagumeDays() );
    }

    /** Day 6 of Pagumé is valid in a leap year. */
    public function test_pagume_day_6_exists_in_leap_year(): void {
        $eth = EthiopianDate::fromEthiopian( 2003, 13, 6 );
        $this->assertSame( 6, $eth->getDay() );
    }

    /** Day 6 of Pagumé throws in a non-leap year. */
    public function test_pagume_day_6_invalid_in_non_leap_year(): void {
        $this->expectException( InvalidArgumentException::class );
        EthiopianDate::fromEthiopian( 2000, 13, 6 );
    }

    /** Day 5 of Pagumé is valid in every year. */
    public function test_pagume_day_5_always_valid(): void {
        EthiopianDate::fromEthiopian( 2000, 13, 5 ); // non-leap
        EthiopianDate::fromEthiopian( 2003, 13, 5 ); // leap
        $this->assertTrue( true ); // no exception = pass
    }

    // -------------------------------------------------------------------------
    // New Year boundary (Meskerem 1)
    // -------------------------------------------------------------------------

    /**
     * New Year = Sept 11 in a regular year.
     * 2017 is not a leap year (2017 % 4 = 1), so New Year 2018 → Sept 11 2025.
     */
    public function test_new_year_sept_11_regular(): void {
        $eth = EthiopianDate::fromEthiopian( 2018, 1, 1 );
        $gc  = $eth->toGregorian();
        $this->assertSame( '2025-09-11', $gc->format( 'Y-m-d' ) );
    }

    /**
     * New Year = Sept 12 in the year before a Gregorian leap year.
     * Gregorian 2008 is a leap year → New Year of 2001 EC (which starts Sept 2008) is Sept 12 2008.
     * 2001 % 4 = 1 (not Ethiopian leap), but it's the year *before* GC 2008 is NOT relevant here —
     * the rule is: Meskerem 1 of the EC year whose New Year falls in the GC year preceding a GC leap.
     * GC leap years: 2008, 2012, 2016, 2020, 2024.
     * The EC New Year that falls in Sept 2007 (before GC 2008): Meskerem 1, 2000 EC = Sept 12 2007. ✓
     */
    public function test_new_year_sept_12_before_gregorian_leap(): void {
        // Already covered by the primary anchor test above; add another pair.
        // Before GC 2012: Meskerem 1, 2004 EC.
        $eth = EthiopianDate::fromEthiopian( 2004, 1, 1 );
        $gc  = $eth->toGregorian();
        $this->assertSame( '2011-09-12', $gc->format( 'Y-m-d' ) );
    }

    // -------------------------------------------------------------------------
    // Round-trip symmetry
    // -------------------------------------------------------------------------

    /** Converting EC → GC → EC should return the original date. */
    public function test_round_trip_eth_to_gc_to_eth(): void {
        $original = EthiopianDate::fromEthiopian( 2010, 6, 15 );
        $gc       = $original->toGregorian();
        $returned = EthiopianDate::fromGregorian( $gc );
        $this->assertSame( $original->getYear(),  $returned->getYear() );
        $this->assertSame( $original->getMonth(), $returned->getMonth() );
        $this->assertSame( $original->getDay(),   $returned->getDay() );
    }

    /** Converting GC → EC → GC should return the original date. */
    public function test_round_trip_gc_to_eth_to_gc(): void {
        $gc       = new DateTimeImmutable( '2023-03-15', new DateTimeZone( 'UTC' ) );
        $eth      = EthiopianDate::fromGregorian( $gc );
        $returned = $eth->toGregorian();
        $this->assertSame( $gc->format( 'Y-m-d' ), $returned->format( 'Y-m-d' ) );
    }

    // -------------------------------------------------------------------------
    // Validation guards
    // -------------------------------------------------------------------------

    public function test_invalid_month_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        EthiopianDate::fromEthiopian( 2017, 14, 1 );
    }

    public function test_invalid_day_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        EthiopianDate::fromEthiopian( 2017, 1, 31 );
    }

    public function test_day_30_valid_for_regular_months(): void {
        $eth = EthiopianDate::fromEthiopian( 2017, 5, 30 );
        $this->assertSame( 30, $eth->getDay() );
    }

    public function test_to_array(): void {
        $eth = EthiopianDate::fromEthiopian( 2000, 1, 1 );
        $this->assertSame( [ 'year' => 2000, 'month' => 1, 'day' => 1 ], $eth->toArray() );
    }

    public function test_to_string(): void {
        $eth = EthiopianDate::fromEthiopian( 2017, 1, 1 );
        $this->assertSame( '2017-01-01', (string) $eth );
    }

    public function test_from_timestamp(): void {
        // Unix timestamp for 2024-09-11 00:00:00 UTC
        $ts  = (int) ( new DateTimeImmutable( '2024-09-11', new DateTimeZone( 'UTC' ) ) )->format( 'U' );
        $eth = EthiopianDate::fromTimestamp( $ts );
        $this->assertSame( 2017, $eth->getYear() );
        $this->assertSame( 1,    $eth->getMonth() );
        $this->assertSame( 1,    $eth->getDay() );
    }
}

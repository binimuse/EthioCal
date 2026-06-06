<?php

namespace EthioCal\Tests;

use EthioCal\Converter\EthiopianDate;
use EthioCal\Converter\Formatter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase {

    private EthiopianDate $ref2000; // 1 Meskerem 2000 EC
    private EthiopianDate $ref2017; // 1 Meskerem 2017 EC

    protected function setUp(): void {
        $this->ref2000 = EthiopianDate::fromEthiopian( 2000, 1, 1 );
        $this->ref2017 = EthiopianDate::fromEthiopian( 2017, 1, 1 );
    }

    // -------------------------------------------------------------------------
    // English formatting
    // -------------------------------------------------------------------------

    public function test_default_format_english(): void {
        $fmt = new Formatter( 'en', 'arabic' );
        $this->assertSame( 'Meskerem 1, 2000', $fmt->format( $this->ref2000 ) );
    }

    public function test_format_tokens_english(): void {
        $fmt = new Formatter( 'en', 'arabic' );
        $this->assertSame( '01', $fmt->format( $this->ref2000, 'd' ) );
        $this->assertSame( '1',  $fmt->format( $this->ref2000, 'j' ) );
        $this->assertSame( '01', $fmt->format( $this->ref2000, 'm' ) );
        $this->assertSame( '1',  $fmt->format( $this->ref2000, 'n' ) );
        $this->assertSame( '2000', $fmt->format( $this->ref2000, 'Y' ) );
        $this->assertSame( 'Meskerem', $fmt->format( $this->ref2000, 'F' ) );
    }

    public function test_pagume_name_english(): void {
        $fmt = new Formatter( 'en' );
        $eth = EthiopianDate::fromEthiopian( 2000, 13, 1 );
        $this->assertSame( 'Pagumé', $fmt->format( $eth, 'F' ) );
    }

    // -------------------------------------------------------------------------
    // Amharic formatting
    // -------------------------------------------------------------------------

    public function test_default_format_amharic(): void {
        $fmt = new Formatter( 'am', 'arabic' );
        $this->assertSame( 'መስከረም 1, 2000', $fmt->format( $this->ref2000 ) );
    }

    public function test_all_amharic_month_names(): void {
        $fmt    = new Formatter( 'am' );
        $names  = $fmt->allMonthNames();
        $this->assertCount( 13, $names );
        $this->assertSame( 'መስከረም', $names[0] );
        $this->assertSame( 'ጳጉሜ',   $names[12] );
    }

    public function test_all_english_month_names(): void {
        $fmt   = new Formatter( 'en' );
        $names = $fmt->allMonthNames();
        $this->assertCount( 13, $names );
        $this->assertSame( 'Meskerem', $names[0] );
        $this->assertSame( 'Pagumé',   $names[12] );
    }

    // -------------------------------------------------------------------------
    // Ge'ez numerals
    // -------------------------------------------------------------------------

    public function test_geez_day_single_digit(): void {
        $fmt = new Formatter( 'en', 'geez' );
        $eth = EthiopianDate::fromEthiopian( 2017, 1, 5 );
        $this->assertSame( '፭', $fmt->format( $eth, 'j' ) );
    }

    public function test_geez_day_double_digit(): void {
        $fmt = new Formatter( 'en', 'geez' );
        $eth = EthiopianDate::fromEthiopian( 2017, 1, 12 );
        $this->assertSame( '፲፪', $fmt->format( $eth, 'j' ) );
    }

    public function test_geez_month_number(): void {
        $fmt = new Formatter( 'en', 'geez' );
        $eth = EthiopianDate::fromEthiopian( 2017, 3, 1 );
        $this->assertSame( '፫', $fmt->format( $eth, 'n' ) );
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_invalid_language_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        new Formatter( 'fr' );
    }

    public function test_invalid_numerals_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        new Formatter( 'en', 'roman' );
    }

    public function test_invalid_month_number_throws(): void {
        $this->expectException( InvalidArgumentException::class );
        ( new Formatter() )->monthName( 0 );
    }
}

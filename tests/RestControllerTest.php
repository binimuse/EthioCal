<?php

namespace EthioCal\Tests;

use EthioCal\Rest\ConvertController;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Tests for GET /wp-json/ethio-cal/v1/convert.
 *
 * get_item() is called directly with a stubbed WP_REST_Request so no WP
 * bootstrap or HTTP stack is needed. The WP REST API arg-schema machinery
 * (enum/validate_callback enforcement) is bypassed; those rules are tested
 * separately via validate_date_param().
 *
 * Reference dates used throughout:
 *   2007-09-12 GC  =  1 Meskerem 2000 EC  (Ethiopian Millennium)
 *   2024-09-11 GC  =  1 Meskerem 2017 EC
 */
class RestControllerTest extends TestCase {

    private ConvertController $ctrl;

    protected function setUp(): void {
        $GLOBALS['_ethiocal_options'] = [];
        $this->ctrl = new ConvertController();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Build a request with sensible defaults for unset params. */
    private function req( array $params ): WP_REST_Request {
        return new WP_REST_Request( array_merge(
            [ 'language' => 'en', 'numerals' => 'arabic', 'format' => 'F j, Y' ],
            $params,
        ) );
    }

    private function ok( WP_REST_Request $request ): array {
        $response = $this->ctrl->get_item( $request );
        $this->assertInstanceOf( WP_REST_Response::class, $response, 'Expected a 200 response.' );
        $this->assertSame( 200, $response->status );
        return $response->data;
    }

    private function err( WP_REST_Request $request ): WP_Error {
        $response = $this->ctrl->get_item( $request );
        $this->assertInstanceOf( WP_Error::class, $response, 'Expected an error response.' );
        return $response;
    }

    // =========================================================================
    // gc-to-ec — happy path
    // =========================================================================

    public function test_gc_to_ec_reference_millennium(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec' ] ) );

        $this->assertSame( 'gc-to-ec',   $data['direction'] );
        $this->assertSame( '2007-09-12', $data['gregorian'] );
        $this->assertSame( 2000,         $data['ethiopian']['year'] );
        $this->assertSame( 1,            $data['ethiopian']['month'] );
        $this->assertSame( 1,            $data['ethiopian']['day'] );
        $this->assertSame( 'Meskerem 1, 2000', $data['ethiopian']['formatted'] );
    }

    public function test_gc_to_ec_reference_2017(): void {
        $data = $this->ok( $this->req( [ 'date' => '2024-09-11', 'direction' => 'gc-to-ec' ] ) );

        $this->assertSame( 2017, $data['ethiopian']['year'] );
        $this->assertSame( 1,   $data['ethiopian']['month'] );
        $this->assertSame( 1,   $data['ethiopian']['day'] );
    }

    public function test_gc_to_ec_response_contains_gregorian_echo(): void {
        $data = $this->ok( $this->req( [ 'date' => '2024-09-11', 'direction' => 'gc-to-ec' ] ) );
        $this->assertSame( '2024-09-11', $data['gregorian'] );
    }

    // =========================================================================
    // ec-to-gc — happy path
    // =========================================================================

    public function test_ec_to_gc_reference_millennium(): void {
        $data = $this->ok( $this->req( [ 'date' => '2000-01-01', 'direction' => 'ec-to-gc' ] ) );

        $this->assertSame( 'ec-to-gc',   $data['direction'] );
        $this->assertSame( '2007-09-12', $data['gregorian'] );
        $this->assertSame( 2000,         $data['ethiopian']['year'] );
        $this->assertSame( 1,            $data['ethiopian']['month'] );
        $this->assertSame( 1,            $data['ethiopian']['day'] );
    }

    public function test_ec_to_gc_reference_2017(): void {
        $data = $this->ok( $this->req( [ 'date' => '2017-01-01', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( '2024-09-11', $data['gregorian'] );
    }

    public function test_ec_to_gc_last_day_of_regular_month(): void {
        // Tikimt (month 2) always has 30 days.
        $data = $this->ok( $this->req( [ 'date' => '2017-02-30', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( 30, $data['ethiopian']['day'] );
    }

    public function test_ec_to_gc_pagume_leap_year(): void {
        // 2003 % 4 == 3 → leap; Pagumé day 6 is valid.
        $data = $this->ok( $this->req( [ 'date' => '2003-13-06', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( 13, $data['ethiopian']['month'] );
        $this->assertSame( 6,  $data['ethiopian']['day'] );
    }

    // =========================================================================
    // Response structure
    // =========================================================================

    public function test_response_contains_all_top_level_keys(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec' ] ) );
        $this->assertArrayHasKey( 'direction', $data );
        $this->assertArrayHasKey( 'gregorian', $data );
        $this->assertArrayHasKey( 'ethiopian', $data );
    }

    public function test_ethiopian_block_contains_year_month_day_formatted(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec' ] ) );
        $eth  = $data['ethiopian'];
        $this->assertArrayHasKey( 'year',      $eth );
        $this->assertArrayHasKey( 'month',     $eth );
        $this->assertArrayHasKey( 'day',       $eth );
        $this->assertArrayHasKey( 'formatted', $eth );
    }

    public function test_gregorian_field_is_iso_date_string(): void {
        $data = $this->ok( $this->req( [ 'date' => '2000-01-01', 'direction' => 'ec-to-gc' ] ) );
        $this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $data['gregorian'] );
    }

    // =========================================================================
    // Language param
    // =========================================================================

    public function test_language_en_produces_english_month(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'language' => 'en' ] ) );
        $this->assertStringContainsString( 'Meskerem', $data['ethiopian']['formatted'] );
    }

    public function test_language_am_produces_amharic_month(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'language' => 'am' ] ) );
        $this->assertStringContainsString( 'መስከረም', $data['ethiopian']['formatted'] );
        $this->assertStringNotContainsString( 'Meskerem', $data['ethiopian']['formatted'] );
    }

    public function test_language_both_contains_english_and_amharic(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'language' => 'both' ] ) );
        $this->assertStringContainsString( 'Meskerem', $data['ethiopian']['formatted'] );
        $this->assertStringContainsString( 'መስከረም',  $data['ethiopian']['formatted'] );
    }

    public function test_language_both_separator_present(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'language' => 'both' ] ) );
        $this->assertStringContainsString( ' / ', $data['ethiopian']['formatted'] );
    }

    // =========================================================================
    // Numerals param
    // =========================================================================

    public function test_numerals_arabic_uses_arabic_digits(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'numerals' => 'arabic' ] ) );
        $this->assertStringContainsString( '2000', $data['ethiopian']['formatted'] );
    }

    public function test_numerals_geez_uses_geez_digits(): void {
        // 2000 EC in Ge'ez = ፳፻
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'numerals' => 'geez' ] ) );
        $this->assertStringContainsString( '፳፻', $data['ethiopian']['formatted'] );
        $this->assertStringNotContainsString( '2000', $data['ethiopian']['formatted'] );
    }

    // =========================================================================
    // Format param
    // =========================================================================

    public function test_custom_format_year_only(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'format' => 'Y' ] ) );
        $this->assertSame( '2000', $data['ethiopian']['formatted'] );
    }

    public function test_custom_format_d_m_Y(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'format' => 'd/m/Y' ] ) );
        $this->assertSame( '01/01/2000', $data['ethiopian']['formatted'] );
    }

    public function test_custom_format_month_name_only(): void {
        $data = $this->ok( $this->req( [ 'date' => '2007-09-12', 'direction' => 'gc-to-ec', 'format' => 'F' ] ) );
        $this->assertSame( 'Meskerem', $data['ethiopian']['formatted'] );
    }

    // =========================================================================
    // Invalid input — gc-to-ec
    // =========================================================================

    public function test_gc_to_ec_invalid_gregorian_month_returns_error(): void {
        // Month 13 does not exist in the Gregorian calendar.
        $err = $this->err( $this->req( [ 'date' => '2007-13-01', 'direction' => 'gc-to-ec' ] ) );
        $this->assertSame( 400, $err->get_error_data()['status'] );
    }

    public function test_gc_to_ec_invalid_day_returns_error(): void {
        // February 30 never exists.
        $err = $this->err( $this->req( [ 'date' => '2007-02-30', 'direction' => 'gc-to-ec' ] ) );
        $this->assertSame( 400, $err->get_error_data()['status'] );
    }

    // =========================================================================
    // Invalid input — ec-to-gc
    // =========================================================================

    public function test_ec_to_gc_invalid_month_14_returns_error(): void {
        $err = $this->err( $this->req( [ 'date' => '2017-14-01', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( 'invalid_date', $err->get_error_code() );
        $this->assertSame( 400, $err->get_error_data()['status'] );
    }

    public function test_ec_to_gc_day_31_in_regular_month_returns_error(): void {
        $err = $this->err( $this->req( [ 'date' => '2017-01-31', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( 400, $err->get_error_data()['status'] );
    }

    public function test_ec_to_gc_pagume_day_6_in_non_leap_year_returns_error(): void {
        // 2017 % 4 == 1 → not a leap year; Pagumé day 6 is invalid.
        $err = $this->err( $this->req( [ 'date' => '2017-13-06', 'direction' => 'ec-to-gc' ] ) );
        $this->assertSame( 400, $err->get_error_data()['status'] );
    }

    // =========================================================================
    // validate_date_param()
    // =========================================================================

    public function test_validate_date_param_accepts_valid_format(): void {
        $this->assertTrue( $this->ctrl->validate_date_param( '2024-09-11' ) );
    }

    public function test_validate_date_param_rejects_non_date_string(): void {
        $result = $this->ctrl->validate_date_param( 'not-a-date' );
        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'invalid_date_format', $result->get_error_code() );
    }

    public function test_validate_date_param_rejects_wrong_separator(): void {
        $this->assertInstanceOf( WP_Error::class, $this->ctrl->validate_date_param( '2024/09/11' ) );
    }

    public function test_validate_date_param_rejects_partial_date(): void {
        $this->assertInstanceOf( WP_Error::class, $this->ctrl->validate_date_param( '2024-09' ) );
    }

    public function test_validate_date_param_rejects_non_string(): void {
        $this->assertInstanceOf( WP_Error::class, $this->ctrl->validate_date_param( 20240911 ) );
    }
}

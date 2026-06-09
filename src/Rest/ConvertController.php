<?php

namespace EthioCal\Rest;

use DateTimeImmutable;
use DateTimeZone;
use EthioCal\Converter\EthiopianDate;
use EthioCal\Converter\Formatter;
use InvalidArgumentException;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

class ConvertController extends WP_REST_Controller {

    protected $namespace = 'ethio-cal/v1';
    protected $rest_base = 'convert';

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_item' ],
                    'permission_callback' => '__return_true',
                    'args'                => $this->get_collection_params(),
                ],
            ],
        );
    }

    /**
     * Argument schema for the convert endpoint.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_collection_params(): array {
        return [
            'date'      => [
                'required'          => true,
                'type'              => 'string',
                'description'       => 'Date to convert (YYYY-MM-DD). '
                    . 'For ec-to-gc supply the Ethiopian year as the year part.',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => [ $this, 'validate_date_param' ],
            ],
            'direction' => [
                'required'          => true,
                'type'              => 'string',
                'enum'              => [ 'gc-to-ec', 'ec-to-gc' ],
                'description'       => 'Conversion direction.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'language'  => [
                'required'          => false,
                'default'           => 'en',
                'type'              => 'string',
                'enum'              => [ 'en', 'am', 'both' ],
                'description'       => 'Language for the formatted output field.',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'numerals'  => [
                'required'          => false,
                'default'           => 'arabic',
                'type'              => 'string',
                'enum'              => [ 'arabic', 'geez' ],
                'description'       => "Numeral system: 'arabic' (1 2 3) or 'geez' (፩ ፪ ፫).",
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'format'    => [
                'required'          => false,
                'default'           => 'F j, Y',
                'type'              => 'string',
                'description'       => 'Formatter token string (F, j, d, n, m, Y).',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Callback
    // -------------------------------------------------------------------------

    /**
     * Handle GET /wp-json/ethio-cal/v1/convert.
     *
     * @param \WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_item( $request ) {
        $direction = (string) $request->get_param( 'direction' );
        $date      = (string) $request->get_param( 'date' );
        $language  = (string) ( $request->get_param( 'language' ) ?? 'en' );
        $numerals  = (string) ( $request->get_param( 'numerals' ) ?? 'arabic' );
        $format    = (string) ( $request->get_param( 'format' ) ?? 'F j, Y' );

        try {
            $data = $direction === 'gc-to-ec'
                ? $this->convertGcToEc( $date, $language, $numerals, $format )
                : $this->convertEcToGc( $date, $language, $numerals, $format );
        } catch ( InvalidArgumentException $e ) {
            return new WP_Error(
                'invalid_date',
                __( 'The provided date is not valid.', 'binimuse-geez-calendar' ),
                [ 'status' => 400 ],
            );
        }

        return new WP_REST_Response( $data, 200 );
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Validate that the date param is a YYYY-MM-DD string.
     * Semantic validity (real calendar date, valid Ethiopian range) is checked
     * inside the callback so direction context is available.
     *
     * @param mixed $value Raw parameter value.
     * @return bool|WP_Error
     */
    public function validate_date_param( $value ) {
        if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return new WP_Error(
                'invalid_date_format',
                __( 'date must be in YYYY-MM-DD format.', 'binimuse-geez-calendar' ),
                [ 'status' => 400 ],
            );
        }
        return true;
    }

    // -------------------------------------------------------------------------
    // Conversion helpers
    // -------------------------------------------------------------------------

    /** @return array<string,mixed> */
    private function convertGcToEc( string $date, string $language, string $numerals, string $format ): array {
        [ $y, $m, $d ] = array_map( 'intval', explode( '-', $date ) );

        if ( ! checkdate( $m, $d, $y ) ) {
            throw new InvalidArgumentException( "Invalid Gregorian date: {$date}." );
        }

        $dt  = new DateTimeImmutable( $date, new DateTimeZone( 'UTC' ) );
        $eth = EthiopianDate::fromGregorian( $dt );

        return $this->buildResponse( 'gc-to-ec', $eth, $date, $language, $numerals, $format );
    }

    /** @return array<string,mixed> */
    private function convertEcToGc( string $date, string $language, string $numerals, string $format ): array {
        [ $y, $m, $d ] = array_map( 'intval', explode( '-', $date ) );

        // fromEthiopian throws InvalidArgumentException for out-of-range inputs.
        $eth       = EthiopianDate::fromEthiopian( $y, $m, $d );
        $gregorian = $eth->toGregorian()->format( 'Y-m-d' );

        return $this->buildResponse( 'ec-to-gc', $eth, $gregorian, $language, $numerals, $format );
    }

    /**
     * Build the unified response array.
     *
     * For language='both' the formatted field combines English and Amharic
     * separated by ' / ' — consistent with how the shortcode renders both.
     *
     * @return array<string,mixed>
     */
    private function buildResponse(
        string $direction,
        EthiopianDate $eth,
        string $gregorian,
        string $language,
        string $numerals,
        string $format,
    ): array {
        $formatted = $this->formatDate( $eth, $language, $numerals, $format );

        return [
            'direction' => $direction,
            'gregorian' => $gregorian,
            'ethiopian' => array_merge(
                $eth->toArray(),
                [ 'formatted' => $formatted ],
            ),
        ];
    }

    private function formatDate( EthiopianDate $eth, string $language, string $numerals, string $format ): string {
        if ( $language === 'both' ) {
            $en = ( new Formatter( 'en', $numerals ) )->format( $eth, $format );
            $am = ( new Formatter( 'am', $numerals ) )->format( $eth, $format );
            return $en . ' / ' . $am;
        }

        $lang = in_array( $language, [ 'en', 'am' ], true ) ? $language : 'en';
        return ( new Formatter( $lang, $numerals ) )->format( $eth, $format );
    }
}

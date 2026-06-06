<?php

/**
 * PHPUnit bootstrap — load Composer autoload then define the minimal WordPress
 * function stubs needed to exercise plugin code outside a full WP environment.
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// ---------------------------------------------------------------------------
// WordPress stubs (only the subset used by EthioCal code under test)
// ---------------------------------------------------------------------------

if ( ! function_exists( 'shortcode_atts' ) ) {
    /**
     * Merge shortcode attributes with their defaults.
     * Mirrors WP core behaviour: unknown keys in $atts are dropped.
     *
     * @param array  $pairs    Supported attributes and their defaults.
     * @param array  $atts     User-supplied attributes.
     * @param string $shortcode Shortcode tag (unused in tests).
     * @return array
     */
    function shortcode_atts( array $pairs, array $atts, string $shortcode = '' ): array {
        $out = [];
        foreach ( $pairs as $key => $default ) {
            $out[ $key ] = array_key_exists( $key, $atts ) ? $atts[ $key ] : $default;
        }
        return $out;
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    /** Escape for HTML output — mirrors WP's esc_html(). */
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }
}

if ( ! function_exists( 'get_option' ) ) {
    /**
     * Retrieve an option value from the in-memory store.
     * Tests seed $GLOBALS['_ethiocal_options'] to simulate saved settings.
     */
    function get_option( string $key, $default = false ) {
        return $GLOBALS['_ethiocal_options'][ $key ] ?? $default;
    }
}

if ( ! function_exists( '__' ) ) {
    /** Return the translation of $text — identity stub for unit tests. */
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    /**
     * Strip HTML tags and normalise whitespace — mirrors WP's sanitize_text_field().
     */
    function sanitize_text_field( string $str ): string {
        return trim( wp_strip_all_tags( $str ) );
    }
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( string $str ): string {
        return strip_tags( $str );
    }
}

// Initialise the in-memory option store used by tests.
$GLOBALS['_ethiocal_options'] = [];

// ---------------------------------------------------------------------------
// WordPress REST API stubs
// ---------------------------------------------------------------------------

if ( ! class_exists( 'WP_REST_Controller' ) ) {
    /**
     * Minimal base-class stub — untyped properties match real WP_REST_Controller,
     * allowing child classes to declare them without a type (as real WP does).
     */
    class WP_REST_Controller {
        protected $namespace = '';
        protected $rest_base = '';
    }
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
    class WP_REST_Server {
        public const READABLE = 'GET';
    }
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
    /**
     * Lightweight request stub. Construct with a flat params array; the
     * controller calls get_param() to retrieve individual values.
     */
    class WP_REST_Request {
        public function __construct( private array $params = [] ) {}

        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        public function __construct(
            public readonly mixed $data,
            public readonly int   $status = 200,
        ) {}
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public function __construct(
            private readonly string $code,
            private readonly string $message,
            private readonly array  $data = [],
        ) {}

        public function get_error_code(): string  { return $this->code; }
        public function get_error_message(): string { return $this->message; }
        public function get_error_data(): array   { return $this->data; }
    }
}

if ( ! function_exists( '__return_true' ) ) {
    function __return_true(): bool { return true; }
}

if ( ! function_exists( 'register_rest_route' ) ) {
    function register_rest_route( string $namespace, string $route, array $args = [] ): bool {
        return true;
    }
}

<?php

namespace EthioCal\Admin;

class SettingsPage {

    public const OPTION_KEY = 'ethio_cal_settings';

    public const DEFAULTS = [
        'format'             => 'F j, Y',
        'language'           => 'en',
        'numerals'           => 'arabic',
        'convert_post_dates' => '0',
    ];

    private const VALID_LANGUAGES = [ 'en', 'am', 'both' ];
    private const VALID_NUMERALS  = [ 'arabic', 'geez' ];

    // -------------------------------------------------------------------------
    // Hook registration
    // -------------------------------------------------------------------------

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu(): void {
        add_options_page(
            __( 'EthioCal Settings', 'ethio-cal' ),
            __( 'EthioCal', 'ethio-cal' ),
            'manage_options',
            'ethio-cal',
            [ $this, 'render' ],
        );
    }

    public function register_settings(): void {
        register_setting(
            'ethio_cal',
            self::OPTION_KEY,
            [
                'sanitize_callback' => [ $this, 'sanitize' ],
                'default'           => self::DEFAULTS,
            ],
        );

        add_settings_section(
            'ethio_cal_general',
            __( 'Display Defaults', 'ethio-cal' ),
            '__return_null',
            'ethio-cal',
        );

        add_settings_field(
            'ethio_cal_format',
            __( 'Date Format', 'ethio-cal' ),
            [ $this, 'render_format_field' ],
            'ethio-cal',
            'ethio_cal_general',
        );

        add_settings_field(
            'ethio_cal_language',
            __( 'Language', 'ethio-cal' ),
            [ $this, 'render_language_field' ],
            'ethio-cal',
            'ethio_cal_general',
        );

        add_settings_field(
            'ethio_cal_numerals',
            __( 'Numeral System', 'ethio-cal' ),
            [ $this, 'render_numerals_field' ],
            'ethio-cal',
            'ethio_cal_general',
        );

        add_settings_field(
            'ethio_cal_convert_post_dates',
            __( 'Post Dates', 'ethio-cal' ),
            [ $this, 'render_convert_field' ],
            'ethio-cal',
            'ethio_cal_general',
        );
    }

    // -------------------------------------------------------------------------
    // Sanitization (public so it is directly unit-testable)
    // -------------------------------------------------------------------------

    /**
     * Validate and sanitize the submitted settings array.
     *
     * @param array<string,string> $input Raw POST data from the settings form.
     * @return array<string,string>       Cleaned values ready for storage.
     */
    public function sanitize( array $input ): array {
        $clean = self::DEFAULTS;

        // format — strip tags; fall back to default when empty after stripping.
        $format          = sanitize_text_field( $input['format'] ?? '' );
        $clean['format'] = $format !== '' ? $format : self::DEFAULTS['format'];

        // language — whitelist; invalid → default.
        $clean['language'] = in_array( $input['language'] ?? '', self::VALID_LANGUAGES, true )
            ? $input['language']
            : self::DEFAULTS['language'];

        // numerals — whitelist; invalid → default.
        $clean['numerals'] = in_array( $input['numerals'] ?? '', self::VALID_NUMERALS, true )
            ? $input['numerals']
            : self::DEFAULTS['numerals'];

        // convert_post_dates — checkbox; absent = '0'.
        $clean['convert_post_dates'] = ! empty( $input['convert_post_dates'] ) ? '1' : '0';

        return $clean;
    }

    // -------------------------------------------------------------------------
    // Field renderers
    // -------------------------------------------------------------------------

    public function render_format_field(): void {
        $opts = get_option( self::OPTION_KEY, self::DEFAULTS );
        printf(
            '<input type="text" id="ethio_cal_format" name="%s[format]" value="%s" class="regular-text">
             <p class="description">%s</p>',
            esc_attr( self::OPTION_KEY ),
            esc_attr( $opts['format'] ),
            esc_html__( 'Tokens: F (month name), j (day), d (day 0-padded), n (month), m (month 0-padded), Y (year).', 'ethio-cal' ),
        );
    }

    public function render_language_field(): void {
        $opts    = get_option( self::OPTION_KEY, self::DEFAULTS );
        $current = $opts['language'];
        $choices = [
            'en'   => __( 'English', 'ethio-cal' ),
            'am'   => __( 'Amharic (አማርኛ)', 'ethio-cal' ),
            'both' => __( 'Both', 'ethio-cal' ),
        ];
        echo '<select id="ethio_cal_language" name="' . esc_attr( self::OPTION_KEY ) . '[language]">';
        foreach ( $choices as $value => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $value ),
                selected( $current, $value, false ),
                esc_html( $label ),
            );
        }
        echo '</select>';
    }

    public function render_numerals_field(): void {
        $opts    = get_option( self::OPTION_KEY, self::DEFAULTS );
        $current = $opts['numerals'];
        $choices = [
            'arabic' => __( 'Arabic (1 2 3)', 'ethio-cal' ),
            'geez'   => __( "Ge'ez (፩ ፪ ፫)", 'ethio-cal' ),
        ];
        echo '<select id="ethio_cal_numerals" name="' . esc_attr( self::OPTION_KEY ) . '[numerals]">';
        foreach ( $choices as $value => $label ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $value ),
                selected( $current, $value, false ),
                esc_html( $label ),
            );
        }
        echo '</select>';
    }

    public function render_convert_field(): void {
        $opts = get_option( self::OPTION_KEY, self::DEFAULTS );
        printf(
            '<label><input type="checkbox" id="ethio_cal_convert_post_dates" name="%s[convert_post_dates]" value="1"%s> %s</label>
             <p class="description">%s</p>',
            esc_attr( self::OPTION_KEY ),
            checked( $opts['convert_post_dates'], '1', false ),
            esc_html__( 'Show Ethiopian date alongside post published dates', 'ethio-cal' ),
            esc_html__( 'Wires up a theme filter — display logic comes in a later step.', 'ethio-cal' ),
        );
    }

    // -------------------------------------------------------------------------
    // Page render
    // -------------------------------------------------------------------------

    public function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'EthioCal Settings', 'ethio-cal' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ethio_cal' );
                do_settings_sections( 'ethio-cal' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

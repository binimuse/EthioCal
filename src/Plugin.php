<?php

namespace EthioCal;

use EthioCal\Admin\SettingsPage;
use EthioCal\Blocks\DateBlock;
use EthioCal\Rest\ConvertController;
use EthioCal\Shortcodes\DateShortcode;

class Plugin {

    public function register(): void {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'register_shortcodes' ] );
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        ( new SettingsPage() )->register();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'binimuse-geez-calendar', false, dirname( plugin_basename( __FILE__ ), 2 ) . '/languages' );
    }

    public function register_shortcodes(): void {
        ( new DateShortcode() )->register();
    }

    public function register_blocks(): void {
        ( new DateBlock() )->register();
    }

    public function register_rest_routes(): void {
        ( new ConvertController() )->register_routes();
    }
}

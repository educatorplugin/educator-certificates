<?php

class Edr_Crt_Bootstrap_Tests {
	private static $instance = null;
	private $edr_path;
	private $edr_crt_path;

	private function __construct() {
		$this->edr_path = dirname( __FILE__ ) . '/../../../ibeducator';
		$this->edr_crt_path = dirname( __FILE__ ) . '/../../../educator-certificates';

		$this->setup();
	}

	private function setup() {
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'localhost';

		$_tests_dir = getenv('WP_TESTS_DIR');

		if ( !$_tests_dir ) {
			$_tests_dir = '/tmp/wordpress-tests-lib';
		}

		require_once $_tests_dir . '/includes/functions.php';

		tests_add_filter( 'muplugins_loaded', array( $this, 'load_plugins' ) );
		tests_add_filter( 'setup_theme', array( $this, 'install_plugins' ) );

		require $_tests_dir . '/includes/bootstrap.php';
		require dirname( __FILE__ ) . '/../lib/edr-crt-test.php';
	}

	public function load_plugins() {
		require $this->edr_crt_path . '/educator-certificates.php';
		require $this->edr_path . '/ibeducator.php';
	}

	public function install_plugins() {
		// Educator.
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-install.php';
		$ibe_install = new IB_Educator_Install();
		$ibe_install->activate();
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

Edr_Crt_Bootstrap_Tests::instance();

<?php
/**
 * Plugin Name: Gtuk Captcha
 * Description: Add a captcha to the login form.
 * Version: 1.0.0
 * Author: Gtuk
 * Author URI: http://gtuk.me
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class GtukCaptcha {

	/**
	 * GtukCaptcha constructor
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'login_head', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'login_form', array( __CLASS__, 'edit_login' ) );
		add_action( 'admin_notices', array( __CLASS__, 'missing_keys' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );

		add_filter( 'wp_authenticate_user', array( __CLASS__, 'check_captcha' ) , 10, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Load plugin internationalisation
	 */
	static function load_textdomain() {
		load_plugin_textdomain( 'gtuk-captcha', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Enqueue scripts
	 */
	static function enqueue_scripts() {
		wp_register_script( 'captcha', 'https://www.google.com/recaptcha/api.js', array(), null, false );
		wp_enqueue_script( 'captcha' );
	}

	/**
	 * Add the captcha to the login form
	 */
	static function edit_login() {
		echo '<div style="margin-bottom: 10px; transform: scale(0.9); transform-origin: 0 0;" class="g-recaptcha" data-sitekey="'.get_option( 'site-key' ).'"></div>';
	}

	/**
	 * Check the captcha result
	 */
	public static function check_captcha( $user, $password ) {
		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
			$user = new WP_Error( 'denied', __( '<strong>'.__( 'ERROR', 'gtuk-captcha' ).'</strong>: '.__( 'Please check the the captcha form', 'gtuk-captcha' ).'.' ) );
		} else {
			$response = file_get_contents( 'https://www.google.com/recaptcha/api/siteverify?secret='.get_option( 'secret-key' ).'&response='.$_POST['g-recaptcha-response'].'&remoteip='.$_SERVER['REMOTE_ADDR'] );
			if ( ! $response['success'] ) {
				remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
				$user = new WP_Error( 'denied', __( '<strong>'.__( 'ERROR', 'gtuk-captcha' ).'</strong>: '.__( 'Wrong captcha', 'gtuk-captcha' ).'.' ) );
			}
		}

		return $user;
	}

	/**
	 * Add settings link to plugin page
	 */
	static function add_settings_link( $links ) {
		$settingsLink = array( '<a href="'.admin_url( 'options-general.php?page=gtuk-captcha' ).'">'.__( 'Settings', 'gtuk-captcha' ).'</a>' );
		return array_merge( $links, $settingsLink );
	}

	/**
	 * Display missing keys notice
	 */
	static function missing_keys() {
		$siteKey = get_option( 'site-key' );
		$secretKey = get_option( 'secret-key' );

		if ( ( empty($siteKey) || empty($secretKey) ) ) {
			echo '<div class="error"><p><strong>'.__( 'Missing reCAPTCHA API keys', 'gtuk-captcha' ).'. <a href="'.admin_url( 'options-general.php?page=gtuk-captcha' ).'">'.__( 'Add keys', 'gtuk-captcha' ).'</a></strong></p></div>';
		}
	}

	/**
	 * Init settings page
	 */
	static function add_settings_page() {
		add_options_page( 'Captcha', 'Captcha', 'manage_options', 'gtuk-captcha', array( __CLASS__, 'captcha_options' ) );
	}

	/**
	 * Display settings page
	 */
	static function captcha_options() {
		?>
		<div class="wrap">
			<h2><?php echo __( 'Captcha options', 'gtuk-captcha' ); ?></h2>
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'update-options' ); ?>
				<p><strong><?php echo __( 'Site key', 'gtuk-captcha' ); ?>:</strong><br />
					<input type="text" name="site-key" size="45" value="<?php echo get_option( 'site-key' ); ?>" />
				</p>
				<p><strong><?php echo __( 'Secret key', 'gtuk-captcha' )?>:</strong><br />
					<input type="text" name="secret-key" size="45" value="<?php echo get_option( 'secret-key' ); ?>" />
				</p>
				<p><input type="submit" name="Submit" value="<?php echo __( 'Save', 'gtuk-captcha' ); ?>" /></p>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="site-key, secret-key" />
			</form>
		</div>
		<?php
	}
}

GtukCaptcha::init();

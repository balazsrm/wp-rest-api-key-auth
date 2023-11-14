<?php
/**
 * Plugin Name: WP REST API Key Authentication
 * Description: A simple plugin to add a new authentication method to the WordPress REST API.
 * Author: Balázs Piller
 * Author URI: https://webwizwork.com
 * Version: 1.0
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest_Api_Key_Auth {

	public function __construct() {
		add_filter( 'rest_authentication_errors', array( $this, 'rest_api_key_auth_check' ) );

		// Settings page with just a text field to store the API key.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Register the settings field.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add the settings link to the Plugins page.
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

		// If no API key is set, generate one.
		if ( ! get_option( 'rest_api_key_auth_api_key' ) ) {
			$this->generate_api_key();
		}

		// If no user is set, set the first admin user as the default.
		if ( ! get_option( 'rest_api_key_auth_user' ) ) {
			$users = get_users( array( 'role' => 'administrator' ) );
			if ( ! empty( $users ) ) {
				update_option( 'rest_api_key_auth_user', $users[0]->ID );
			}
		}
	}

	/**
	 * Check if the API key is valid.
	 *
	 * @param $access
	 *
	 * @return bool|WP_Error
	 */
	public function rest_api_key_auth_check( $result ) {
		// If a previous authentication check was made, return that result.
		if ( true === $result || is_wp_error( $result ) ) {
			return $result;
		}

		// Check for the 'X-Api-Key' header
		$api_key = isset( $_SERVER['HTTP_X_API_KEY'] ) ? $_SERVER['HTTP_X_API_KEY'] : null;

		// If no API key is present, do nothing.
		if ( ! $api_key ) {
			return $result;
		}

		// Verify the API Key.
		if ( ! $this->api_key_is_valid( $api_key ) ) {
			// If the API key is incorrect, return an error.
			return new WP_Error(
				'rest_invalid_api_key',
				'Invalid API Key provided.',
				array( 'status' => 403 )
			);
		}

		// If the API key is valid, set the current user to the user associated with the API key.
		$user_id = get_option( 'rest_api_key_auth_user' );
		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			wp_set_current_user( $user_id, $user->user_login );
			wp_set_auth_cookie( $user_id );
		} else {
			// If the user does not exist, return an error.
			return new WP_Error(
				'rest_invalid_api_key',
				'Invalid API Key provided.',
				array( 'status' => 403 )
			);
		}

		// If we got this far, the API key is valid, so return true.
		return true;
	}

	/**
	 * Check if the API key is valid.
	 *
	 * @param $api_key
	 *
	 * @return bool
	 */
	private function api_key_is_valid( $api_key ) {
		// Get the API key saved in the plugin settings.
		$saved_api_key = get_option( 'rest_api_key_auth_api_key' );

		// Check if the API keys match.
		if ( $api_key === $saved_api_key ) {
			return true;
		}

		return false;
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			'Rest API Key Authentication',
			'Rest API Key Authentication',
			'manage_options',
			'rest-api-key-auth',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Display the settings page.
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h2>Rest API Key Authentication</h2>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'rest-api-key-auth' );
				do_settings_sections( 'rest-api-key-auth' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register the settings.
	 */
	public function register_settings() {
		// Create settings section.
		$section = 'rest_api_key_auth_section';
		// For the section
		add_settings_section(
			$section,
			'API Key',
			array( $this, 'settings_section' ),
			'rest-api-key-auth'
		);

		// Create API key field.
		$field = 'rest_api_key_auth_api_key';
		add_settings_field(
			$field,
			'API Key',
			array( $this, 'api_key_field' ),
			'rest-api-key-auth',
			$section,
			array( 'label_for' => $field )
		);

		// Register API key field.
		register_setting(
			'rest-api-key-auth',
			$field,
			array( $this, 'sanitize_api_key' )
		);

		// Create user selector: choose the user to associate with the API key.
		$field = 'rest_api_key_auth_user';
		add_settings_field(
			$field,
			'User',
			array( $this, 'user_field' ),
			'rest-api-key-auth',
			$section,
			array( 'label_for' => $field )
		);

		// Register user selector.
		register_setting(
			'rest-api-key-auth',
			$field,
			array( $this, 'sanitize_user' )
		);
	}

	/**
	 * Display the settings section.
	 */
	public function settings_section() {
		echo 'Enter the API key and select the user to associate with the API key.';
	}

	/**
	 * Display the API key field.
	 */
	public function api_key_field() {
		$value = get_option( 'rest_api_key_auth_api_key' );
		?>
		<input type="text" class="long-text" id="rest_api_key_auth_api_key" name="rest_api_key_auth_api_key" value="<?php echo esc_attr( $value ); ?>" />
		<?php
	}

	/**
	 * Sanitize the API key.
	 */
	public function sanitize_api_key( $key ) {
		return sanitize_text_field( $key );
	}

	/**
	 * Display the user selector. Simple numeric text field if there are more than 100 users, dropdown otherwise.
	 */
	public function user_field() {
		$value = get_option( 'rest_api_key_auth_user' );
		$count = count_users()['total_users'];
		if ( $count['total_users'] > 100 ) {
			?>
			<input type="text" class="small-text" id="rest_api_key_auth_user" name="rest_api_key_auth_user" value="<?php echo esc_attr( $value ); ?>" />
			<?php
		} else {
			$users = get_users();
			?>
			<select id="rest_api_key_auth_user" name="rest_api_key_auth_user">
				<?php foreach ( $users as $user ) : ?>
					<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $value, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Sanitize the user ID.
	 */
	public function sanitize_user( $user ) {
		return absint( $user );
	}

	/**
	 * Generate a new API key.
	 */
	private function generate_api_key() {
		// Generate a random MD5 string.
		$api_key = md5( uniqid( rand(), true ) );

		// Save the API key in the plugin settings.
		update_option( 'rest_api_key_auth_api_key', $api_key );
	}

	/**
	 * Add the settings link to the Plugins page.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=rest-api-key-auth">Settings</a>';
		array_push( $links, $settings_link );
		return $links;
	}

}

$rest_api_key_auth = new Rest_Api_Key_Auth();

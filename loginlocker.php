<?php
/*
Plugin Name: Login Locker
Plugin URI: https://tomroyal.com/2022/07/11/login-locker-for-wordpress
Description: Lock down Wordpress Login against bots by requiring a querystring key
Version: 1.0.0
Author: Tom Royal
Author URI: https://tomroyal.com
License: GPL2
*/

// settings interface

class LoginLocker {
	private $login_locker_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'login_locker_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'login_locker_page_init' ) );
	}

	public function login_locker_add_plugin_page() {
		add_menu_page(
			'Login Locker', // page_title
			'Login Locker', // menu_title
			'manage_options', // capability
			'login-locker', // menu_slug
			array( $this, 'login_locker_create_admin_page' ), // function
			'dashicons-admin-generic', // icon_url
			2 // position
		);
	}

	public function login_locker_create_admin_page() {
		$this->login_locker_options = get_option( 'login_locker_option_name' ); 
		$llocker_login_url = get_site_url().'/wp-login.php?llkey='.$this->login_locker_options['key_0'];
		?>

		<div class="wrap">
			<h2>Login Locker</h2>
			<p>Enter your chosen security key code - something short is fine, no spaces, and not a password!</p>
			<p>Once enabled, login at: <?php echo($llocker_login_url);?></p>
			
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'login_locker_option_group' );
					do_settings_sections( 'login-locker-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function login_locker_page_init() {
		register_setting(
			'login_locker_option_group', // option_group
			'login_locker_option_name', // option_name
			array( $this, 'login_locker_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'login_locker_setting_section', // id
			'Settings', // title
			array( $this, 'login_locker_section_info' ), // callback
			'login-locker-admin' // page
		);

		add_settings_field(
			'key_0', // id
			'Key Code', // title
			array( $this, 'key_0_callback' ), // callback
			'login-locker-admin', // page
			'login_locker_setting_section' // section
		);
	}

	public function login_locker_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['key_0'] ) ) {
			$sanitary_values['key_0'] = sanitize_text_field( $input['key_0'] );
		}

		return $sanitary_values;
	}

	public function login_locker_section_info() {
		
	}

	public function key_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="login_locker_option_name[key_0]" id="key_0" value="%s">',
			isset( $this->login_locker_options['key_0'] ) ? esc_attr( $this->login_locker_options['key_0']) : ''
		);
	}

}

// if wp-admin, do the above

if ( is_admin() ){
  $login_locker = new LoginLocker();
}

// get the configured key

$llocker_options = get_option( 'login_locker_option_name' );
$valid_llkey = $llocker_options['key_0'];

// core functions

function check_login_key() {
  global $valid_llkey;
	// only if key is set..
	if ($valid_llkey != ''){
		$llocker_key = $_REQUEST['llkey'];
		if ($llocker_key != $valid_llkey){
			header("HTTP/1.1 401 Unauthorized");
			exit();
		}
	}	
}

function add_login_key_to_form() {
  global $valid_llkey;
  echo('<input type="hidden" name="llkey" value="'.$valid_llkey.'" />');
}

function add_login_key_to_logout( $logout_url, $redirect ) {
    global $valid_llkey;
    return ($logout_url.'&llkey='.$valid_llkey);
}

add_action( 'login_init', 'check_login_key' );
add_action( 'login_form', 'add_login_key_to_form' );
add_filter( 'logout_url', 'add_login_key_to_logout', 10, 2 );

?>

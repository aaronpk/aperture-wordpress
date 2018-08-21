<?php
/**
 * Plugin Name: Aperture
 * Plugin URI: https://github.com/aaronpk/aperture-wordpress
 * Description: This plugin adds a Microsub endpoint to your WordPress site by using the hosted Aperture service.
 * Version: 1.0.2
 * Author: Aaron Parecki
 * Author URI: https://aaronparecki.com
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 */

define( 'APERTURE_SERVER', 'https://aperture.p3k.io' );

class Aperture_Plugin {

  public function __construct() {
    #add_action( 'register_activation_hook', array( $this, 'register_activation_hook' ) );
    register_activation_hook( __FILE__, array( $this, 'activated' ) );
    add_action( 'wp_head', array( $this, 'html_header' ) );
    add_action( 'rest_api_init', array( $this, 'register_routes' ) );

    if( get_option( 'aperture_registration_error' ) ) {
      add_action( 'admin_notices', array( $this, 'display_error' ) );
    }

    if( get_option( 'aperture_registration_success' ) ) {
      add_action( 'admin_notices', array( $this, 'display_success' ) );
    }
  }

  public function register_routes() {
    register_rest_route(
      'aperture/1.0', '/verification', array(
        array(
          'methods'  => WP_REST_Server::CREATABLE,
          'callback' => array( $this, 'verification' ),
          'args'     => array(),
        ),
      )
    );
  }

  public function activated() {

    // Check for the IndieAuth plugin and show an error if it's not installed
    if( !class_exists( 'IndieAuth_Admin' ) ) {
      deactivate_plugins( plugin_basename( __FILE__ ) );
      wp_die( 'This plugin requires the <a href="https://wordpress.org/plugins/indieauth/">WordPress IndieAuth plugin</a>. Please go back and install that plugin first.' );
    }

    // Register a new account on Aperture and store the resulting Microsub endpoint

    // Generate a temporary code so that Aperture can verify this request
    $code = wp_generate_password( 128, false );

    // Store the code in the options  table
    update_option( 'aperture_temporary_code', $code );

    $verification_endpoint = rest_url( '/aperture/1.0/verification' );

    $version = get_plugin_data( __FILE__)['Version'];

    $args       = array(
      'headers' => array(
        'Accept'       => 'application/json',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ),
      'body'     => array(
        'verification_endpoint' => $verification_endpoint,
        'code'   => $code,
        'site'   => home_url( '/' ), // this needs to be the same URL that the IndieAuth plugin returns as the identity
        'via'    => 'aperture-wordpress/' . $version,
      ),
    );
    $endpoint  = APERTURE_SERVER.'/api/register';

    $response  = wp_remote_post( $endpoint, $args );
  }

  public function verification() {
    // Aperture always sends the code we generated, make sure it's in the database
    if( isset($_POST['code']) ) {

      if( get_option( 'aperture_temporary_code' ) != $_POST['code']) {
        return new WP_REST_Response( array( 'error' => 'invalid code' ), 400 );
      }

      if( isset( $_POST['challenge'] ) ) {
        // WP_REST_Response seems to always encode the parameter as JSON, meaning this ends up returning "foo" with quotes
        #return new WP_REST_Response( $_POST['challenge'], 200, array( 'Content-Type' => 'text/plain' ) );
        header('Content-Type: text/plain');
        echo $_POST['challenge'];
        die();
      }

      if( isset( $_POST['error'] ) ) {
        // Aperture reports validation errors here
        update_option( 'aperture_registration_error', $_POST['error'] );
        return new WP_REST_Response( 'ok' );
      }

      if( isset( $_POST['microsub'] ) ) {
        update_option( 'aperture_microsub_url', $_POST['microsub'] );
        update_option( 'aperture_registration_success', 'Successfully registered a new account! You can now log in to Aperture and Microsub readers!' );
        return new WP_REST_Response( 'ok' );
      }

    }
  }

  public function display_error() {
    $message = get_option( 'aperture_registration_error' );
    ?>
    <div class="notice notice-error is-dismissible">
      <p><?php _e( 'Aperture Registration Error: ' . $message ) ?></p>
    </div>
    <?php
    delete_option( 'aperture_registration_error' );
  }

  public function display_success() {
    $message = get_option( 'aperture_registration_success' );
    ?>
    <div class="notice notice-success is-dismissible">
      <p><b>Aperture:</b> <?php _e( $message ) ?></p>
    </div>
    <?php
    delete_option( 'aperture_registration_success' );
  }

  public function html_header() {
    if( $url = get_option('aperture_microsub_url') ) {
      ?>
      <link rel="microsub" href="<?php echo $url ?>">
      <?php
    }
  }

}

new Aperture_Plugin();

<?php
/*
Plugin Name: Stupid Simple Login Logo
Description: Easily change the logo displayed on the WordPress login page.
Version:     1.3.4
Author:      Dynamic Technologies
Author URI:  https://bedynamic.tech
Plugin URI:  https://github.com/bedynamictech/Stupid-Simple-Login-Logo
License:     GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ssll
Domain Path: /languages
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Option key (namespaced to avoid collisions)
define( 'SSLL_OPTION', 'ssll_logo_url' );

/**
 * Load plugin textdomain for translations
 */
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'ssll',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

/**
 * Add top‑level menu and submenu
 */
add_action( 'admin_menu', function() {
    // Top‑level menu (dashboard can also link to our settings)
    add_menu_page(
        __( 'Stupid Simple', 'ssll' ),          // page title
        __( 'Stupid Simple', 'ssll' ),          // menu title
        'manage_options',                       // capability
        'stupidsimple',                         // menu slug
        'ssll_settings_page_content',           // callback
        'dashicons-hammer',                     // icon
        99                                      // position
    );

    // Submenu: Login Logo settings
    add_submenu_page(
        'stupidsimple',
        __( 'Login Logo', 'ssll' ),             // page title
        __( 'Login Logo', 'ssll' ),             // submenu title
        'manage_options',                       // capability
        'login-logo',                           // menu slug
        'ssll_settings_page_content'            // callback (same page)
    );
} );

/**
 * Enqueue WP Media scripts only on our settings page
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    // Only enqueue on our submenu page
    if ( 'stupidsimple_page_login-logo' !== $hook && 'toplevel_page_stupidsimple' !== $hook ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script( 'jquery' );
} );

/**
 * Register our setting, section, and field
 */
add_action( 'admin_init', function() {
    register_setting( 'ssll_settings_group', SSLL_OPTION, 'esc_url_raw' );

    add_settings_section(
        'ssll_logo_section',
        __( 'Login Logo Settings', 'ssll' ),
        function() {
            echo '<p>' . esc_html__( 'Upload or enter the URL of the image to use as your custom login logo.', 'ssll' ) . '</p>';
        },
        'login-logo'
    );

    add_settings_field(
        'ssll_logo_field',
        __( 'Logo URL', 'ssll' ),
        'ssll_logo_field_callback',
        'login-logo',
        'ssll_logo_section'
    );
} );

/**
 * Render the logo URL input + upload/remove button
 */
function ssll_logo_field_callback() {
    $logo_url = get_option( SSLL_OPTION, '' );
    ?>
    <input
        type="text"
        id="wp_logo_url"
        name="<?php echo esc_attr( SSLL_OPTION ); ?>"
        value="<?php echo esc_attr( $logo_url ); ?>"
        class="regular-text"
    />
    <input
        type="button"
        id="logo-btn"
        class="button"
        value="<?php echo esc_attr( $logo_url ? __( 'Remove Logo', 'ssll' ) : __( 'Upload Logo', 'ssll' ) ); ?>"
    />
    <p class="description">
        <?php esc_html_e( 'Use the button to upload/select, or paste in an image URL.', 'ssll' ); ?>
    </p>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        var logoBtn      = $('#logo-btn');
        var logoUrlInput = $('#wp_logo_url');

        function updateButtonText() {
            var hasLogo = logoUrlInput.val().length > 0;
            logoBtn.val( hasLogo
                ? '<?php echo esc_js( __( 'Remove Logo', 'ssll' ) ); ?>'
                : '<?php echo esc_js( __( 'Upload Logo', 'ssll' ) ); ?>'
            );
        }

        logoBtn.on('click', function(e){
            e.preventDefault();
            if ( logoUrlInput.val() ) {
                // Remove logo
                $.post( ajaxurl, {
                    action: 'remove_login_logo',
                    nonce: '<?php echo wp_create_nonce( 'remove_login_logo_nonce' ); ?>'
                }, function(response){
                    if ( response.success ) {
                        logoUrlInput.val('');
                        updateButtonText();
                    } else {
                        alert('<?php echo esc_js( __( 'Failed to remove logo.', 'ssll' ) ); ?>');
                    }
                });
            } else {
                // Upload/select logo
                var mediaFrame = wp.media({
                    title: '<?php echo esc_js( __( 'Select Login Logo', 'ssll' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this image', 'ssll' ) ); ?>' },
                    multiple: false
                });
                mediaFrame.on('select', function(){
                    var attachment = mediaFrame.state().get('selection').first().toJSON();
                    logoUrlInput.val( attachment.url );
                    updateButtonText();
                });
                mediaFrame.open();
            }
        });

        updateButtonText();
    });
    </script>
    <?php
}

/**
 * Settings page wrapper
 */
function ssll_settings_page_content() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Login Logo Settings', 'ssll' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'ssll_settings_group' );
            do_settings_sections( 'login-logo' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * AJAX handler: remove the saved logo URL
 */
add_action( 'wp_ajax_remove_login_logo', function() {
    check_ajax_referer( 'remove_login_logo_nonce', 'nonce' );
    update_option( SSLL_OPTION, '' );
    wp_send_json_success();
} );

/**
 * Output custom CSS for the login page logo
 */
add_action( 'login_head', function() {
    $logo_url = esc_url( get_option( SSLL_OPTION, '' ) );
    if ( $logo_url ) {
        echo '<style type="text/css">
            h1 a {
                background-image: url(' . $logo_url . ') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                width: auto !important;
                height: 100px !important;
            }
        </style>';
    }
} );

/**
 * Change the login logo URL to point to the site homepage
 */
add_filter( 'login_headerurl', function( $url ) {
    return esc_url( home_url() );
} );

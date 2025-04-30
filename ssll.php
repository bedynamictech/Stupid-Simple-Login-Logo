<?php
/**
 * Plugin Name: Stupid Simple Login Logo
 * Description: Easily change the logo displayed on the Login page.
 * Version: 1.4
 * Author: Dynamic Technologies
 * Author URI: http://bedynamic.tech
 * Plugin URI: https://github.com/bedynamictech/StupidSimplePlugins/tree/main/ssll
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constant for option key
define( 'SSLL_OPTION', 'wp_logo_url' );

// Add admin menu
add_action( 'admin_menu', 'ssll_add_menu' );

function ssll_add_menu() {
    global $menu;
    $parent_exists = false;
    foreach ( $menu as $item ) {
        if ( ! empty( $item[2] ) && $item[2] === 'stupidsimple' ) {
            $parent_exists = true;
            break;
        }
    }

    if ( ! $parent_exists ) {
        add_menu_page(
            'Stupid Simple',
            'Stupid Simple',
            'manage_options',
            'stupidsimple',
            function () {
                wp_redirect( 'https://bedynamic.tech/stupid-simple/' );
                exit;
            },
            'dashicons-hammer',
            99
        );
    }

    add_submenu_page(
        'stupidsimple',
        'Login Logo',
        'Login Logo',
        'manage_options',
        'login-logo',
        'ssll_settings_page_content'
    );
}

// Add Settings link on Plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ssll_action_links' );

function ssll_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=login-logo' ) . '">Settings</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// Register settings
add_action( 'admin_init', 'ssll_register_settings' );

function ssll_register_settings() {
    register_setting( 'ssll_settings_group', SSLL_OPTION, 'esc_url_raw' );

    add_settings_section(
        'ssll_logo_section',
        '',
        'ssll_logo_section_callback',
        'login-logo'
    );
}

// Output settings section
function ssll_logo_section_callback() {
    $logo_url = get_option( SSLL_OPTION );
    ?>
    <div style="margin-top: 20px;">
        <input type="text" id="wp_logo_url" name="<?php echo esc_attr( SSLL_OPTION ); ?>" value="<?php echo esc_attr( $logo_url ); ?>" class="regular-text" />
        <input type="button" id="logo-btn" class="button" value="<?php echo esc_attr( $logo_url ? __( 'Remove Logo', 'ssll' ) : __( 'Upload Logo', 'ssll' ) ); ?>" />
        <p class="description"><?php esc_html_e( 'Use the upload button, or enter the URL of an image.', 'ssll' ); ?></p>
    </div>
    <?php
}

// Settings page content
function ssll_settings_page_content() {
    wp_enqueue_script( 'jquery' );
    wp_enqueue_media();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Login Logo', 'ssll' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'ssll_settings_group' );
            do_settings_sections( 'login-logo' );
            submit_button();
            ?>
        </form>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var logoUrlInput = $('#wp_logo_url');
                var logoBtn = $('#logo-btn');

                function updateButtonText() {
                    logoBtn.val(logoUrlInput.val() ? '<?php echo esc_js( __( 'Remove Logo', 'ssll' ) ); ?>' : '<?php echo esc_js( __( 'Upload Logo', 'ssll' ) ); ?>');
                }

                updateButtonText();

                logoBtn.click(function(e) {
                    e.preventDefault();

                    if (logoBtn.val() === '<?php echo esc_js( __( 'Remove Logo', 'ssll' ) ); ?>') {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'remove_login_logo',
                                nonce: '<?php echo wp_create_nonce( 'remove_login_logo_nonce' ); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    logoUrlInput.val('');
                                    updateButtonText();
                                    location.reload();
                                } else {
                                    alert('<?php echo esc_js( __( 'Error removing logo.', 'ssll' ) ); ?>');
                                }
                            }
                        });
                    } else {
                        var image = wp.media({
                            title: '<?php echo esc_js( __( 'Select Image', 'ssll' ) ); ?>',
                            multiple: false
                        }).open().on('select', function() {
                            var uploaded_image = image.state().get('selection').first();
                            var image_url = uploaded_image.toJSON().url;
                            logoUrlInput.val(image_url);
                            updateButtonText();
                            $('form').submit();
                        });
                    }
                });
            });
        </script>
    </div>
    <?php
}

// AJAX handler to remove logo
add_action( 'wp_ajax_remove_login_logo', 'remove_login_logo' );

function remove_login_logo() {
    check_ajax_referer( 'remove_login_logo_nonce', 'nonce' );
    update_option( SSLL_OPTION, '' );
    wp_send_json_success();
}

// Add custom logo to login screen
add_action( 'login_head', 'wordpress_custom_login_logo' );

function wordpress_custom_login_logo() {
    $logo_url = esc_url( get_option( SSLL_OPTION ) );

    if ( ! empty( $logo_url ) ) {
        echo '<style type="text/css">
            h1 a {
                background-image: url(' . esc_url( $logo_url ) . ') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center center !important;
                height: 100px !important;
                width: auto !important;
                display: block !important;
                text-indent: -9999px !important;
                overflow: hidden !important;
            }
        </style>';
    }
}

// Change login logo link to site home
add_filter( 'login_headerurl', 'change_login_logo_url' );

function change_login_logo_url( $url ) {
    return esc_url( home_url() );
}

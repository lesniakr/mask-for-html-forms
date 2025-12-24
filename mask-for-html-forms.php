<?php
/**
 * Plugin Name:       Mask for HTML Forms
 * Plugin URI:        https://github.com/lesniakr/mask-for-html-forms
 * Description:       Extends HTML Forms plugin with input masking capabilities using jQuery Mask Plugin. Add phone, date, postal code, and custom masks to your form fields.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Rafał Leśniak
 * Author URI:        https://rafallesniak.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mask-for-html-forms
 * Domain Path:       /languages
 *
 * @package MaskForHtmlForms
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
define( 'MFHF_VERSION', '1.0.0' );
define( 'MFHF_PLUGIN_FILE', __FILE__ );
define( 'MFHF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MFHF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MFHF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if HTML Forms plugin is active.
 *
 * @return bool True if HTML Forms is active, false otherwise.
 */
function mfhf_is_html_forms_active(): bool {
    return class_exists( 'HTML_Forms\\Plugin' ) || defined( 'HTML_FORMS_VERSION' );
}

/**
 * Display admin notice when HTML Forms is not active.
 *
 * @return void
 */
function mfhf_admin_notice_missing_dependency(): void {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'Mask for HTML Forms', 'mask-for-html-forms' ); ?></strong>
            <?php esc_html_e( 'requires', 'mask-for-html-forms' ); ?>
            <a href="https://wordpress.org/plugins/html-forms/" target="_blank">HTML Forms</a>
            <?php esc_html_e( 'plugin to be installed and activated.', 'mask-for-html-forms' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize the plugin.
 *
 * @return void
 */
function mfhf_init(): void {
    // Check for HTML Forms dependency.
    if ( ! mfhf_is_html_forms_active() ) {
        add_action( 'admin_notices', 'mfhf_admin_notice_missing_dependency' );
        return;
    }

    // Load plugin text domain for translations.
    load_plugin_textdomain(
        'mask-for-html-forms',
        false,
        dirname( MFHF_PLUGIN_BASENAME ) . '/languages'
    );

    // Load required files.
    require_once MFHF_PLUGIN_DIR . 'includes/class-plugin.php';

    // Boot the plugin.
    \MaskForHtmlForms\Plugin::get_instance();
}

// Hook into plugins_loaded to ensure HTML Forms is loaded first.
add_action( 'plugins_loaded', 'mfhf_init' );

/**
 * Plugin activation hook.
 *
 * @return void
 */
function mfhf_activate(): void {
    // Activation tasks if needed in the future.
    // For now, we just flush rewrite rules.
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'mfhf_activate' );

/**
 * Plugin deactivation hook.
 *
 * @return void
 */
function mfhf_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'mfhf_deactivate' );

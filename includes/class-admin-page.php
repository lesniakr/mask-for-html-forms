<?php
/**
 * Admin page class.
 *
 * Provides a help/documentation page in the WordPress admin
 * with mask examples and usage instructions.
 *
 * @package MaskForHtmlForms
 * @since   1.0.0
 */

namespace MaskForHtmlForms;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin_Page class.
 *
 * Handles the admin interface for the plugin.
 *
 * @since 1.0.0
 */
class Admin_Page {

    /**
     * Admin page slug.
     *
     * @var string
     */
    const PAGE_SLUG = 'mask-for-html-forms';

    /**
     * Option key for inline errors default.
     *
     * @var string
     */
    const OPTION_INLINE_ERRORS = 'mfhf_show_inline_errors';

    /**
     * Admin notice message.
     *
     * @var string|null
     */
    private ?string $notice = null;

    /**
     * Constructor.
     *
     * Sets up admin hooks.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress admin hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'plugin_action_links_' . MFHF_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
    }

    /**
     * Add submenu page under HTML Forms menu.
     *
     * @return void
     */
    public function add_submenu_page(): void {
        add_submenu_page(
            'html-forms',  // Parent slug (HTML Forms menu).
            __( 'Input Masks', 'mask-for-html-forms' ),
            __( 'Input Masks', 'mask-for-html-forms' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function enqueue_admin_assets( string $hook_suffix ): void {
        // Only load on our admin page.
        if ( 'html-forms_page_' . self::PAGE_SLUG !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'mfhf-admin',
            MFHF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MFHF_VERSION
        );
    }

    /**
     * Add links to the plugin action links.
     *
     * @param array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function add_plugin_action_links( array $links ): array {
        $custom_links = array(
            sprintf(
                '<a href="%s">%s</a>',
                admin_url( 'admin.php?page=' . self::PAGE_SLUG ),
                __( 'Documentation', 'mask-for-html-forms' )
            ),
        );

        return array_merge( $custom_links, $links );
    }

    /**
     * Render the admin page content.
     *
     * @return void
     */
    public function render_page(): void {
        $this->handle_settings_form();
        ?>
        <div class="wrap mfhf-admin-page">
            <h1><?php esc_html_e( 'Mask for HTML Forms', 'mask-for-html-forms' ); ?></h1>

            <?php $this->render_notice(); ?>

            <div class="mfhf-intro">
                <p><?php esc_html_e( 'Add input masks to your HTML Forms fields using simple HTML attributes. Input masks help users enter data in the correct format by automatically formatting their input.', 'mask-for-html-forms' ); ?></p>
            </div>

            <?php $this->render_settings_section(); ?>
            <?php $this->render_quick_start_section(); ?>
            <?php $this->render_presets_section(); ?>
            <?php $this->render_custom_masks_section(); ?>
            <?php $this->render_mask_options_section(); ?>
            <?php $this->render_examples_section(); ?>
        </div>
        <?php
    }

    /**
     * Handle settings form submission.
     *
     * @return void
     */
    private function handle_settings_form(): void {
        if ( ! isset( $_POST['mfhf_settings_nonce'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mfhf_settings_nonce'] ) ), 'mfhf_save_settings' ) ) {
            return;
        }

        $inline_errors_enabled = isset( $_POST['mfhf_show_inline_errors'] ) && '1' === $_POST['mfhf_show_inline_errors'];

        update_option( self::OPTION_INLINE_ERRORS, $inline_errors_enabled ? 1 : 0 );

        $this->notice = __( 'Settings saved.', 'mask-for-html-forms' );
    }

    /**
     * Render admin notice if set.
     *
     * @return void
     */
    private function render_notice(): void {
        if ( ! $this->notice ) {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $this->notice ) . '</p></div>';
    }

    /**
     * Render settings section.
     *
     * @return void
     */
    private function render_settings_section(): void {
        $inline_errors_enabled = (bool) get_option( self::OPTION_INLINE_ERRORS, false );
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Settings', 'mask-for-html-forms' ); ?></h2>
            <form method="post">
                <?php wp_nonce_field( 'mfhf_save_settings', 'mfhf_settings_nonce' ); ?>
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="mfhf_show_inline_errors" value="1" <?php checked( $inline_errors_enabled ); ?> />
                    <span><?php esc_html_e( 'Show inline mask messages under fields by default (data-mask-show-error = true).', 'mask-for-html-forms' ); ?></span>
                </label>
                <p class="description">
                    <?php esc_html_e( 'You can override per field with the data-mask-show-error attribute.', 'mask-for-html-forms' ); ?>
                </p>
                <p>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'mask-for-html-forms' ); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render the quick start section.
     *
     * @return void
     */
    private function render_quick_start_section(): void {
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Quick Start', 'mask-for-html-forms' ); ?></h2>
            <p><?php esc_html_e( 'To add a mask to any input field in HTML Forms, simply add the data-mask attribute:', 'mask-for-html-forms' ); ?></p>

            <div class="mfhf-code-example">
                <code>&lt;input type="text" name="phone" data-mask="000 000 000" /&gt;</code>
            </div>

            <p><?php esc_html_e( 'Or use a preset mask with the data-mask-preset attribute:', 'mask-for-html-forms' ); ?></p>

            <div class="mfhf-code-example">
                <code>&lt;input type="text" name="phone" data-mask-preset="phone-pl" /&gt;</code>
            </div>
        </div>
        <?php
    }

    /**
     * Render the presets section.
     *
     * @return void
     */
    private function render_presets_section(): void {
        $presets = array(
            'phone-pl'      => array(
                'label'   => __( 'Polish Phone', 'mask-for-html-forms' ),
                'mask'    => '000 000 000',
                'example' => '123 456 789',
            ),
            'phone-pl-intl' => array(
                'label'   => __( 'Polish Phone (International)', 'mask-for-html-forms' ),
                'mask'    => '+00 000 000 000',
                'example' => '+48 123 456 789',
            ),
            'postal-pl'     => array(
                'label'   => __( 'Polish Postal Code', 'mask-for-html-forms' ),
                'mask'    => '00-000',
                'example' => '00-001',
            ),
            'pesel'         => array(
                'label'   => __( 'PESEL', 'mask-for-html-forms' ),
                'mask'    => '00000000000',
                'example' => '12345678901',
            ),
            'nip'           => array(
                'label'   => __( 'NIP', 'mask-for-html-forms' ),
                'mask'    => '000-000-00-00',
                'example' => '123-456-78-90',
            ),
            'regon'         => array(
                'label'   => __( 'REGON', 'mask-for-html-forms' ),
                'mask'    => '000000000',
                'example' => '123456789',
            ),
            'date-eu'       => array(
                'label'   => __( 'Date (DD/MM/YYYY)', 'mask-for-html-forms' ),
                'mask'    => '00/00/0000',
                'example' => '25/12/2024',
            ),
            'date-iso'      => array(
                'label'   => __( 'Date (ISO)', 'mask-for-html-forms' ),
                'mask'    => '0000-00-00',
                'example' => '2024-12-25',
            ),
            'time-24'       => array(
                'label'   => __( 'Time (24h)', 'mask-for-html-forms' ),
                'mask'    => '00:00',
                'example' => '14:30',
            ),
            'credit-card'   => array(
                'label'   => __( 'Credit Card', 'mask-for-html-forms' ),
                'mask'    => '0000 0000 0000 0000',
                'example' => '1234 5678 9012 3456',
            ),
            'iban-pl'       => array(
                'label'   => __( 'IBAN (Polish)', 'mask-for-html-forms' ),
                'mask'    => 'AA 00 0000 0000 0000 0000 0000 0000',
                'example' => 'PL 12 3456 7890 1234 5678 9012 3456',
            ),
        );
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Available Presets', 'mask-for-html-forms' ); ?></h2>
            <p><?php esc_html_e( 'Use these preset names with the data-mask-preset attribute:', 'mask-for-html-forms' ); ?></p>

            <table class="wp-list-table widefat fixed striped mfhf-presets-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Preset Name', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Mask Pattern', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Example Output', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'HTML Code', 'mask-for-html-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $presets as $preset_name => $preset_data ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $preset_name ); ?></code></td>
                            <td><?php echo esc_html( $preset_data['label'] ); ?></td>
                            <td><code><?php echo esc_html( $preset_data['mask'] ); ?></code></td>
                            <td><?php echo esc_html( $preset_data['example'] ); ?></td>
                            <td>
                                <code class="mfhf-copyable">&lt;input type="text" name="field" data-mask-preset="<?php echo esc_attr( $preset_name ); ?>" /&gt;</code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the custom masks section.
     *
     * @return void
     */
    private function render_custom_masks_section(): void {
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Custom Mask Patterns', 'mask-for-html-forms' ); ?></h2>
            <p><?php esc_html_e( 'Create custom masks using these pattern characters:', 'mask-for-html-forms' ); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 100px;"><?php esc_html_e( 'Character', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'mask-for-html-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>0</code></td>
                        <td><?php esc_html_e( 'Required digit (0-9)', 'mask-for-html-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>9</code></td>
                        <td><?php esc_html_e( 'Optional digit (0-9)', 'mask-for-html-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>#</code></td>
                        <td><?php esc_html_e( 'Recursive digit (for variable-length numbers)', 'mask-for-html-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>A</code></td>
                        <td><?php esc_html_e( 'Required alphanumeric (A-Z, a-z, 0-9)', 'mask-for-html-forms' ); ?></td>
                    </tr>
                    <tr>
                        <td><code>S</code></td>
                        <td><?php esc_html_e( 'Required letter (A-Z, a-z)', 'mask-for-html-forms' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 15px;">
                <?php esc_html_e( 'Any other characters (like spaces, dashes, slashes) are used as literal separators.', 'mask-for-html-forms' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the mask options section.
     *
     * @return void
     */
    private function render_mask_options_section(): void {
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Mask Options', 'mask-for-html-forms' ); ?></h2>
            <p><?php esc_html_e( 'Additional attributes to customize mask behavior:', 'mask-for-html-forms' ); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Attribute', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'mask-for-html-forms' ); ?></th>
                        <th><?php esc_html_e( 'Example', 'mask-for-html-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>data-mask-reverse</code></td>
                        <td><?php esc_html_e( 'Apply mask from right to left (useful for currency)', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-reverse="true"</code></td>
                    </tr>
                    <tr>
                        <td><code>data-mask-clearifnotmatch</code></td>
                        <td><?php esc_html_e( 'Clear the field if input does not match the mask completely', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-clearifnotmatch="true"</code></td>
                    </tr>
                    <tr>
                        <td><code>data-mask-selectonfocus</code></td>
                        <td><?php esc_html_e( 'Select all text when field receives focus', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-selectonfocus="true"</code></td>
                    </tr>
                    <tr>
                        <td><code>data-mask-show-error</code></td>
                        <td><?php esc_html_e( 'Show inline error message under this field (overrides global setting)', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-show-error="true"</code></td>
                    </tr>
                    <tr>
                        <td><code>data-mask-error</code></td>
                        <td><?php esc_html_e( 'Custom inline error text when the mask is invalid', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-error="Please follow the format"</code></td>
                    </tr>
                    <tr>
                        <td><code>data-mask-error-class</code></td>
                        <td><?php esc_html_e( 'Custom CSS class for the inline error element', 'mask-for-html-forms' ); ?></td>
                        <td><code>data-mask-error-class="my-inline-error"</code></td>
                    </tr>
                    <tr>
                        <td><code>placeholder</code></td>
                        <td><?php esc_html_e( 'Standard HTML placeholder attribute (shows format hint)', 'mask-for-html-forms' ); ?></td>
                        <td><code>placeholder="DD/MM/YYYY"</code></td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 10px;">
                <?php esc_html_e( 'Inline errors are disabled by default. Enable globally via the mfhf_script_settings filter or per field with data-mask-show-error.', 'mask-for-html-forms' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render the full examples section.
     *
     * @return void
     */
    private function render_examples_section(): void {
        ?>
        <div class="mfhf-section">
            <h2><?php esc_html_e( 'Complete Form Example', 'mask-for-html-forms' ); ?></h2>
            <p><?php esc_html_e( 'Here is a complete example form with various masked fields:', 'mask-for-html-forms' ); ?></p>

            <div class="mfhf-code-block">
                <pre><code>&lt;p&gt;
    &lt;label for="phone"&gt;Phone Number&lt;/label&gt;
    &lt;input type="text" name="phone" id="phone"
           data-mask-preset="phone-pl"
           placeholder="000 000 000" required /&gt;
&lt;/p&gt;

&lt;p&gt;
    &lt;label for="postal"&gt;Postal Code&lt;/label&gt;
    &lt;input type="text" name="postal" id="postal"
           data-mask="00-000"
           placeholder="00-000" required /&gt;
&lt;/p&gt;

&lt;p&gt;
    &lt;label for="pesel"&gt;PESEL&lt;/label&gt;
    &lt;input type="text" name="pesel" id="pesel"
           data-mask-preset="pesel"
           placeholder="00000000000" /&gt;
&lt;/p&gt;

&lt;p&gt;
    &lt;label for="birthdate"&gt;Birth Date&lt;/label&gt;
    &lt;input type="text" name="birthdate" id="birthdate"
           data-mask="00/00/0000"
           data-mask-clearifnotmatch="true"
           placeholder="DD/MM/YYYY" /&gt;
&lt;/p&gt;

&lt;p&gt;
    &lt;label for="price"&gt;Price&lt;/label&gt;
    &lt;input type="text" name="price" id="price"
           data-mask="#.##0,00"
           data-mask-reverse="true"
           placeholder="0,00" /&gt;
&lt;/p&gt;

&lt;p&gt;
    &lt;input type="submit" value="Submit" /&gt;
&lt;/p&gt;</code></pre>
            </div>
        </div>

        <div class="mfhf-section mfhf-tips">
            <h2><?php esc_html_e( 'Tips', 'mask-for-html-forms' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'Always add a placeholder attribute to show users the expected format.', 'mask-for-html-forms' ); ?></li>
                <li><?php esc_html_e( 'For date fields, consider using HTML5 date input type instead of masks when appropriate.', 'mask-for-html-forms' ); ?></li>
                <li><?php esc_html_e( 'Test your forms on mobile devices to ensure masks work well with touch keyboards.', 'mask-for-html-forms' ); ?></li>
                <li><?php esc_html_e( 'Use data-mask-clearifnotmatch for fields that must be complete to be valid.', 'mask-for-html-forms' ); ?></li>
            </ul>
        </div>
        <?php
    }
}

<?php
/**
 * Main plugin orchestrator class.
 *
 * Responsible for initializing all plugin components and coordinating
 * their interactions. Acts as the central hub for the plugin.
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
 * Plugin class.
 *
 * Singleton pattern ensures only one instance of the plugin runs.
 *
 * @since 1.0.0
 */
final class Plugin {

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Assets loader instance.
     *
     * @var Assets_Loader|null
     */
    private ?Assets_Loader $assets_loader = null;

    /**
     * Form detector instance.
     *
     * @var Form_Detector|null
     */
    private ?Form_Detector $form_detector = null;

    /**
     * Admin page instance.
     *
     * @var Admin_Page|null
     */
    private ?Admin_Page $admin_page = null;

    /**
     * Get the singleton instance.
     *
     * @return Plugin The plugin instance.
     */
    public static function get_instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
    }

    /**
     * Load required class files.
     *
     * @return void
     */
    private function load_dependencies(): void {
        require_once MFHF_PLUGIN_DIR . 'includes/class-form-detector.php';
        require_once MFHF_PLUGIN_DIR . 'includes/class-assets-loader.php';
        require_once MFHF_PLUGIN_DIR . 'includes/class-admin-page.php';
    }

    /**
     * Initialize plugin components.
     *
     * @return void
     */
    private function init_components(): void {
        $this->form_detector = new Form_Detector();
        $this->assets_loader = new Assets_Loader( $this->form_detector );
        $this->admin_page    = new Admin_Page();
    }

    /**
     * Get the assets loader instance.
     *
     * @return Assets_Loader The assets loader.
     */
    public function get_assets_loader(): Assets_Loader {
        return $this->assets_loader;
    }

    /**
     * Get the form detector instance.
     *
     * @return Form_Detector The form detector.
     */
    public function get_form_detector(): Form_Detector {
        return $this->form_detector;
    }

    /**
     * Get the admin page instance.
     *
     * @return Admin_Page The admin page.
     */
    public function get_admin_page(): Admin_Page {
        return $this->admin_page;
    }

    /**
     * Prevent cloning of the instance.
     *
     * @return void
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance.
     *
     * @return void
     * @throws \Exception Always throws exception.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }
}

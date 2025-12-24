<?php
/**
 * Assets loader class.
 *
 * Handles conditional loading of JavaScript and CSS assets
 * only when HTML Forms are present on the page.
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
 * Assets_Loader class.
 *
 * Manages script and style enqueuing for the plugin.
 *
 * @since 1.0.0
 */
class Assets_Loader {

    /**
     * Form detector instance.
     *
     * @var Form_Detector
     */
    private Form_Detector $form_detector;

    /**
     * Script handle for jQuery Mask Plugin.
     *
     * @var string
     */
    const JQUERY_MASK_HANDLE = 'jquery-mask-plugin';

    /**
     * Script handle for our initialization script.
     *
     * @var string
     */
    const INIT_SCRIPT_HANDLE = 'mfhf-mask-init';

    /**
     * Constructor.
     *
     * @param Form_Detector $form_detector The form detector instance.
     */
    public function __construct( Form_Detector $form_detector ) {
        $this->form_detector = $form_detector;
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        // Use wp_footer with late priority to ensure form detection has occurred.
        add_action( 'wp_footer', array( $this, 'maybe_enqueue_scripts' ), 5 );

        // Register scripts early so they can be enqueued later.
        add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 5 );
    }

    /**
     * Register scripts without enqueuing them.
     *
     * Scripts are registered early but only enqueued when needed.
     *
     * @return void
     */
    public function register_scripts(): void {
        // Register jQuery Mask Plugin.
        wp_register_script(
            self::JQUERY_MASK_HANDLE,
            MFHF_PLUGIN_URL . 'assets/js/jquery.mask.min.js',
            array( 'jquery' ),
            '1.14.16',
            true
        );

        // Register our initialization script.
        wp_register_script(
            self::INIT_SCRIPT_HANDLE,
            MFHF_PLUGIN_URL . 'assets/js/mask-init.js',
            array( 'jquery', self::JQUERY_MASK_HANDLE ),
            MFHF_VERSION,
            true
        );

        // Localize script with settings.
        wp_localize_script(
            self::INIT_SCRIPT_HANDLE,
            'mfhfSettings',
            $this->get_script_settings()
        );
    }

    /**
     * Conditionally enqueue scripts if a form is present.
     *
     * Called in wp_footer to ensure form detection via filter has occurred.
     *
     * @return void
     */
    public function maybe_enqueue_scripts(): void {
        // Allow developers to force-enable scripts.
        $force_load = apply_filters( 'mfhf_force_load_scripts', false );

        if ( ! $force_load && ! $this->form_detector->has_form() ) {
            return;
        }

        $this->enqueue_scripts();
    }

    /**
     * Enqueue all required scripts.
     *
     * @return void
     */
    private function enqueue_scripts(): void {
        wp_enqueue_script( self::JQUERY_MASK_HANDLE );
        wp_enqueue_script( self::INIT_SCRIPT_HANDLE );

        /**
         * Fires after mask scripts are enqueued.
         *
         * @since 1.0.0
         */
        do_action( 'mfhf_scripts_enqueued' );
    }

    /**
     * Get settings to pass to JavaScript.
     *
     * @return array Settings array.
     */
    private function get_script_settings(): array {
        $settings = array(
            // Selector for form container.
            'formSelector'      => '.hf-form',

            // Selector for masked inputs.
            'inputSelector'     => '[data-mask]',

            // Whether to watch for dynamically added inputs.
            'watchDynamicInputs' => true,

            // Custom translations for mask patterns.
            'translations'      => $this->get_mask_translations(),

            // Preset masks for common use cases.
            'presets'           => $this->get_mask_presets(),
        );

        /**
         * Filter the JavaScript settings.
         *
         * @since 1.0.0
         * @param array $settings The settings array.
         */
        return apply_filters( 'mfhf_script_settings', $settings );
    }

    /**
     * Get custom mask character translations.
     *
     * @return array Translations array.
     */
    private function get_mask_translations(): array {
        $translations = array(
            // P = Polish letter (including diacritics).
            'P' => array(
                'pattern'  => '[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]',
                'optional' => false,
            ),
        );

        /**
         * Filter mask character translations.
         *
         * @since 1.0.0
         * @param array $translations The translations array.
         */
        return apply_filters( 'mfhf_mask_translations', $translations );
    }

    /**
     * Get preset masks for common formats.
     *
     * These can be referenced by name using data-mask-preset attribute.
     *
     * @return array Presets array.
     */
    private function get_mask_presets(): array {
        $presets = array(
            'phone-pl'      => array(
                'mask'    => '000 000 000',
                'options' => array(),
            ),
            'phone-pl-intl' => array(
                'mask'    => '+00 000 000 000',
                'options' => array(),
            ),
            'postal-pl'     => array(
                'mask'    => '00-000',
                'options' => array(),
            ),
            'pesel'         => array(
                'mask'    => '00000000000',
                'options' => array(),
            ),
            'nip'           => array(
                'mask'    => '000-000-00-00',
                'options' => array(),
            ),
            'regon'         => array(
                'mask'    => '000000000',
                'options' => array(),
            ),
            'date-eu'       => array(
                'mask'    => '00/00/0000',
                'options' => array( 'placeholder' => 'DD/MM/YYYY' ),
            ),
            'date-iso'      => array(
                'mask'    => '0000-00-00',
                'options' => array( 'placeholder' => 'YYYY-MM-DD' ),
            ),
            'time-24'       => array(
                'mask'    => '00:00',
                'options' => array( 'placeholder' => 'HH:MM' ),
            ),
            'credit-card'   => array(
                'mask'    => '0000 0000 0000 0000',
                'options' => array(),
            ),
            'iban-pl'       => array(
                'mask'    => 'AA 00 0000 0000 0000 0000 0000 0000',
                'options' => array(),
            ),
        );

        /**
         * Filter mask presets.
         *
         * @since 1.0.0
         * @param array $presets The presets array.
         */
        return apply_filters( 'mfhf_mask_presets', $presets );
    }

    /**
     * Get the form detector instance.
     *
     * @return Form_Detector The form detector.
     */
    public function get_form_detector(): Form_Detector {
        return $this->form_detector;
    }
}

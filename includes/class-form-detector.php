<?php
/**
 * Form detector class.
 *
 * Detects whether the current page contains an HTML Forms form,
 * allowing for conditional loading of assets.
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
 * Form_Detector class.
 *
 * Handles detection of HTML Forms on the current page.
 *
 * @since 1.0.0
 */
class Form_Detector {

    /**
     * Flag indicating if a form was detected.
     *
     * @var bool
     */
    private bool $form_detected = false;

    /**
     * Flag indicating if detection has been performed.
     *
     * @var bool
     */
    private bool $detection_done = false;

    /**
     * Constructor.
     *
     * Sets up the detection hooks.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        // Hook into the form HTML filter to detect when a form is rendered.
        add_filter( 'hf_form_html', array( $this, 'on_form_render' ), 10, 2 );

        // Also check post content for shortcodes as a fallback.
        add_action( 'wp', array( $this, 'detect_in_content' ) );
    }

    /**
     * Callback when a form is rendered.
     *
     * Uses the hf_form_html filter to detect form presence.
     * This is the most reliable detection method.
     *
     * @param string $html The form HTML.
     * @param object $form The form object.
     * @return string The unmodified form HTML.
     */
    public function on_form_render( string $html, $form ): string {
        $this->form_detected  = true;
        $this->detection_done = true;

        return $html;
    }

    /**
     * Detect forms in post content by looking for shortcodes.
     *
     * This is a fallback method that runs early to enable
     * asset enqueuing decisions before wp_enqueue_scripts.
     *
     * @return void
     */
    public function detect_in_content(): void {
        if ( $this->detection_done ) {
            return;
        }

        global $post;

        if ( ! $post instanceof \WP_Post ) {
            return;
        }

        // Check for HTML Forms shortcodes in content.
        $shortcodes_to_check = array( 'hf_form', 'html_form' );

        foreach ( $shortcodes_to_check as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $this->form_detected  = true;
                $this->detection_done = true;
                return;
            }
        }

        // Check for block editor HTML Forms block.
        if ( function_exists( 'has_block' ) && has_block( 'html-forms/form', $post ) ) {
            $this->form_detected  = true;
            $this->detection_done = true;
            return;
        }

        $this->detection_done = true;
    }

    /**
     * Check if a form has been detected on the current page.
     *
     * @return bool True if a form was detected, false otherwise.
     */
    public function has_form(): bool {
        return $this->form_detected;
    }

    /**
     * Force detection status.
     *
     * Useful for testing or manual override via hooks.
     *
     * @param bool $detected Whether a form should be considered detected.
     * @return void
     */
    public function set_form_detected( bool $detected ): void {
        $this->form_detected  = $detected;
        $this->detection_done = true;
    }

    /**
     * Reset detection state.
     *
     * Useful for testing purposes.
     *
     * @return void
     */
    public function reset(): void {
        $this->form_detected  = false;
        $this->detection_done = false;
    }
}

/**
 * Mask for HTML Forms - Initialization Script
 *
 * Initializes jQuery Mask Plugin on HTML Forms fields that have
 * data-mask or data-mask-preset attributes.
 *
 * @package MaskForHtmlForms
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Main initialization object.
     */
    var MaskForHtmlForms = {

        /**
         * Plugin settings (passed from PHP via wp_localize_script).
         */
        settings: window.mfhfSettings || {},

        /**
         * Initialize the mask functionality.
         */
        init: function() {
            this.applyMasks();
            this.setupDynamicMasks();
            this.setupFormEvents();
        },

        /**
         * Apply masks to all matching inputs.
         */
        applyMasks: function() {
            var self = this;

            // Apply preset masks first.
            $('[data-mask-preset]').each(function() {
                self.applyPresetMask($(this));
            });

            // Apply data-mask attributes (handled by jQuery Mask Plugin automatically).
            // We just need to ensure custom translations are available.
            this.extendTranslations();

            // Trigger jQuery Mask Plugin's data-mask detection.
            $.applyDataMask();
        },

        /**
         * Apply a preset mask to an element.
         *
         * @param {jQuery} $element The input element.
         */
        applyPresetMask: function($element) {
            var presetName = $element.attr('data-mask-preset');
            var presets = this.settings.presets || {};

            if (!presets[presetName]) {
                console.warn('Mask for HTML Forms: Unknown preset "' + presetName + '"');
                return;
            }

            var preset = presets[presetName];
            var options = $.extend({}, preset.options || {});

            // Apply the mask.
            $element.mask(preset.mask, options);
        },

        /**
         * Extend jQuery Mask Plugin with custom translations.
         */
        extendTranslations: function() {
            var translations = this.settings.translations || {};

            // Add custom translations to global settings.
            $.each(translations, function(char, config) {
                if (config.pattern) {
                    $.jMaskGlobals.translation[char] = {
                        pattern: new RegExp(config.pattern),
                        optional: config.optional || false,
                        recursive: config.recursive || false
                    };
                }
            });
        },

        /**
         * Setup watching for dynamically added inputs.
         */
        setupDynamicMasks: function() {
            if (!this.settings.watchDynamicInputs) {
                return;
            }

            var self = this;

            // Use MutationObserver to watch for new inputs.
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length) {
                            self.applyMasksToNewNodes(mutation.addedNodes);
                        }
                    });
                });

                // Observe the document body for added nodes.
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        },

        /**
         * Apply masks to newly added DOM nodes.
         *
         * @param {NodeList} nodes Added nodes.
         */
        applyMasksToNewNodes: function(nodes) {
            var self = this;

            $(nodes).each(function() {
                var $node = $(this);

                // Check if the node itself has a mask.
                if ($node.is('[data-mask-preset]')) {
                    self.applyPresetMask($node);
                } else if ($node.is('[data-mask]')) {
                    $.applyDataMask($node);
                }

                // Check children.
                $node.find('[data-mask-preset]').each(function() {
                    self.applyPresetMask($(this));
                });

                $node.find('[data-mask]').each(function() {
                    $.applyDataMask($(this));
                });
            });
        },

        /**
         * Setup form event handlers.
         */
        setupFormEvents: function() {
            var formSelector = this.settings.formSelector || '.hf-form';

            // Hook into HTML Forms events if available.
            if (typeof html_forms !== 'undefined') {
                // Re-apply masks after form submission (in case form is reset).
                html_forms.on('success', function(form) {
                    setTimeout(function() {
                        $.applyDataMask($(form));
                    }, 100);
                });
            }

            // Handle form reset.
            $(document).on('reset', formSelector, function() {
                var $form = $(this);

                // Re-apply masks after reset.
                setTimeout(function() {
                    $form.find('[data-mask], [data-mask-preset]').each(function() {
                        var $input = $(this);
                        var mask = $input.data('mask');

                        if (mask && typeof mask.remove === 'function') {
                            mask.remove();
                        }
                    });

                    $.applyDataMask($form);
                }, 10);
            });
        },

        /**
         * Get the clean (unmasked) value of an input.
         *
         * @param {jQuery|string} selector Input element or selector.
         * @return {string} Clean value without mask characters.
         */
        getCleanValue: function(selector) {
            var $element = $(selector);

            if ($element.length && typeof $element.cleanVal === 'function') {
                return $element.cleanVal();
            }

            return $element.val();
        },

        /**
         * Manually apply a mask to an element.
         *
         * @param {jQuery|string} selector Element or selector.
         * @param {string} mask Mask pattern.
         * @param {object} options Mask options.
         */
        applyMask: function(selector, mask, options) {
            $(selector).mask(mask, options || {});
        },

        /**
         * Remove mask from an element.
         *
         * @param {jQuery|string} selector Element or selector.
         */
        removeMask: function(selector) {
            $(selector).unmask();
        }
    };

    // Initialize on DOM ready.
    $(document).ready(function() {
        MaskForHtmlForms.init();
    });

    // Expose to global scope for external access.
    window.MaskForHtmlForms = MaskForHtmlForms;

})(jQuery);

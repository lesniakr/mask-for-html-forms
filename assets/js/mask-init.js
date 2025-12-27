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
         * Track whether mask wrapper is installed.
         */
        maskWrapped: false,

        /**
         * Track whether inline error styles are injected.
         */
        errorStylesInjected: false,

        /**
         * Plugin settings (passed from PHP via wp_localize_script).
         */
        settings: window.mfhfSettings || {},

        /**
         * Format helper for localized messages.
         */
        formatMessage: function(template, args) {
            if (!template) {
                return '';
            }

            return template.replace(/%(\d+)\$s/g, function(match, number) {
                var index = parseInt(number, 10) - 1;
                return typeof args[index] !== 'undefined' ? args[index] : match;
            }).replace(/%s/g, function() {
                var val = args.shift();
                return typeof val !== 'undefined' ? val : '';
            });
        },

        /**
         * Safely retrieve a localized message with an optional fallback.
         *
         * @param {string} key Message key.
         * @param {string} fallback Fallback text.
         * @return {string} Message text.
         */
        getMessage: function(key, fallback) {
            var messages = this.settings.messages || {};

            if (messages[key]) {
                return messages[key];
            }

            return fallback || '';
        },

        /**
         * Initialize the mask functionality.
         */
        init: function() {
            this.installInlineErrorSupport();
            this.applyMasks();
            this.setupDynamicMasks();
            this.setupFormEvents();
            this.setupBlurCheck();
            this.setupInvalidHandler();
            this.setupLiveValidation();
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
        },

        /**
         * Wrap jQuery Mask to inject inline error callbacks.
         */
        installInlineErrorSupport: function() {
            if (this.maskWrapped || typeof $.fn.mask !== 'function') {
                return;
            }

            var self = this;
            var originalMask = $.fn.mask;

            $.fn.mask = function(mask, options) {
                var $targets = $(this);
                var opts = self.withInlineErrorCallbacks($targets, options);

                // Store mask pattern for completeness checks.
                $targets.each(function() {
                    $(this).data('mfhfMaskPattern', mask);
                });

                return originalMask.call(this, mask, opts);
            };

            this.maskWrapped = true;
        },

        /**
         * Extend mask callbacks with inline error rendering.
         *
         * @param {jQuery} $elements Target elements.
         * @param {object} options  Mask options.
         * @return {object} Options with wrapped callbacks.
         */
        withInlineErrorCallbacks: function($elements, options) {
            var self = this;
            var opts = $.extend({}, options);

            var originalInvalid = opts.onInvalid;
            var originalComplete = opts.onComplete;
            var originalKeyPress = opts.onKeyPress;
            var originalChange = opts.onChange;

            opts.onInvalid = function(val, e, f, invalid, mask) {
                self.handleInlineInvalid($(this), invalid);

                if (typeof originalInvalid === 'function') {
                    originalInvalid.call(this, val, e, f, invalid, mask);
                }
            };

            opts.onComplete = function() {
                self.clearInlineError($(this));

                if (typeof originalComplete === 'function') {
                    originalComplete.apply(this, arguments);
                }
            };

            opts.onKeyPress = function() {
                var $el = $(this);
                self.maybeClearInlineError($el);
                self.handleInlineIncomplete($el);

                if (typeof originalKeyPress === 'function') {
                    originalKeyPress.apply(this, arguments);
                }
            };

            opts.onChange = function() {
                var $el = $(this);
                self.maybeClearInlineError($el);
                self.handleInlineIncomplete($el);

                if (typeof originalChange === 'function') {
                    originalChange.apply(this, arguments);
                }
            };

            return opts;
        },

        /**
         * Attach blur handler to show errors when field is incomplete.
         */
        setupBlurCheck: function() {
            var self = this;
            var baseSelector = this.settings.inputSelector || '[data-mask]';
            var selector = baseSelector + ', [data-mask-preset], [data-mask-error], [data-mask-show-error]';

            $(document).on('blur', selector, function() {
                self.handleInlineIncomplete($(this));
            });
        },

        /**
         * Validate fields live while typing.
         */
        setupLiveValidation: function() {
            var self = this;
            var baseSelector = this.settings.inputSelector || '[data-mask]';
            var selector = baseSelector + ', [data-mask-preset], [data-mask-error], [data-mask-show-error]';

            $(document).on('input', selector, function() {
                var $el = $(this);

                if ($el.is('[data-mask], [data-mask-preset]')) {
                    self.handleInlineIncomplete($el);
                } else {
                    self.handleGenericLive($el);
                }
            });
        },

        /**
         * Prevent native browser validation UI for masked inputs when inline errors are shown.
         */
        setupInvalidHandler: function() {
            var self = this;

            document.addEventListener('invalid', function(event) {
                var target = event.target;

                if (!target || !target.getAttribute) {
                    return;
                }

                var $el = $(target);

                if (!self.shouldShowInlineErrors($el)) {
                    return;
                }

                if (!$el.is('[data-mask], [data-mask-preset], [data-mask-error], [data-mask-show-error]')) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                // For masked fields this will show completeness/required; for others we fall back to generic invalid.
                if ($el.is('[data-mask], [data-mask-preset]')) {
                    self.handleInlineIncomplete($el);
                } else {
                    self.handleGenericInvalid($el);
                }
            }, true);
        },

        /**
         * Render inline error when mask validation fails.
         *
         * @param {jQuery} $element Target element.
         * @param {Array} invalid Invalid entries from jQuery Mask.
         */
        handleInlineInvalid: function($element, invalid) {
            if (!this.shouldShowInlineErrors($element)) {
                return;
            }

            var message = this.getInlineErrorMessage($element, invalid);

            if (!message) {
                return;
            }

            this.ensureErrorStyles();
            this.renderInlineError($element, message);
        },

        /**
         * Handle incomplete values (e.g., too few characters) on blur.
         *
         * @param {jQuery} $element Target element.
         */
        handleInlineIncomplete: function($element) {
            if (!this.shouldShowInlineErrors($element)) {
                return;
            }

            if (this.isEmptyRequired($element)) {
                var requiredMessage = this.getRequiredMessage($element);
                this.ensureErrorStyles();
                this.renderInlineError($element, requiredMessage);
                return;
            }

            if (!this.isIncomplete($element)) {
                this.clearInlineError($element);
                return;
            }

            var info = this.getCompletenessInfo($element);
            var message = this.getIncompleteMessage($element, info.required, info.current);

            if (!message) {
                return;
            }

            this.ensureErrorStyles();
            this.renderInlineError($element, message);
        },

        /**
         * Decide whether to show inline errors for an element.
         *
         * @param {jQuery} $element Target element.
         * @return {boolean} True when inline errors should render.
         */
        shouldShowInlineErrors: function($element) {
            var attr = $element.attr('data-mask-show-error');

            if (typeof attr !== 'undefined') {
                return this.parseBoolean(attr, true);
            }

            return !!this.settings.showInlineErrors;
        },

        /**
         * Convert common truthy/falsey strings to boolean.
         *
         * @param {string|boolean} value Input value.
         * @param {boolean} defaultValue Fallback when value is empty.
         * @return {boolean} Parsed boolean.
         */
        parseBoolean: function(value, defaultValue) {
            if (value === undefined || value === null || value === '') {
                return defaultValue;
            }

            if (typeof value === 'boolean') {
                return value;
            }

            var normalized = String(value).toLowerCase();

            if (normalized === 'false' || normalized === '0' || normalized === 'no') {
                return false;
            }

            if (normalized === 'true' || normalized === '1' || normalized === 'yes') {
                return true;
            }

            return defaultValue;
        },

        /**
         * Build inline error message.
         *
         * @param {jQuery} $element Target element.
         * @param {Array} invalid Invalid entries.
         * @return {string} Message text.
         */
        getInlineErrorMessage: function($element, invalid) {
            var customMessage = $element.attr('data-mask-error');

            if (customMessage) {
                return customMessage;
            }

            if (invalid && invalid.length) {
                var first = invalid[0];
                var position = typeof first.p === 'number' ? first.p + 1 : '';
                var value = first.v || '';
                var expected = first.e || 'pattern';

                if (position) {
                    return this.formatMessage(this.getMessage('invalidValueWithPos', 'Invalid value "%1$s" at position %2$s (expected %3$s)'), [value, position, expected]);
                }

                return this.formatMessage(this.getMessage('invalidValue', 'Invalid value "%1$s" (expected %2$s)'), [value, expected]);
            }

            return this.getMessage('invalidGeneric', 'Invalid value for this field');
        },

        /**
         * Build incomplete message based on required vs. current length.
         *
         * @param {jQuery} $element Target element.
         * @param {number} required Required length.
         * @param {number} current Current length.
         * @return {string} Message text.
         */
        getIncompleteMessage: function($element, required, current) {
            var customMessage = $element.attr('data-mask-error');
            if (customMessage) {
                return customMessage;
            }

            var missing = Math.max(required - current, 0);

            if (!missing) {
                return '';
            }

            return this.formatMessage(this.getMessage('incomplete', 'Complete the format (missing %s characters)'), [missing]);
        },

        /**
         * Get required field message.
         *
         * @param {jQuery} $element Target element.
         * @return {string} Message text.
         */
        getRequiredMessage: function($element) {
            var customMessage = $element.attr('data-mask-error');
            if (customMessage) {
                return customMessage;
            }

            return this.getMessage('required', 'This field is required');
        },

        /**
         * Handle generic invalid event for non-masked fields that opt into inline errors.
         *
         * @param {jQuery} $element Target element.
         */
        handleGenericInvalid: function($element) {
            if (!this.shouldShowInlineErrors($element)) {
                return;
            }

            var lengthMessage = this.getLengthMessage($element);

            if (lengthMessage) {
                this.ensureErrorStyles();
                this.renderInlineError($element, lengthMessage);
                return;
            }

            if (this.isEmptyRequired($element)) {
                this.ensureErrorStyles();
                this.renderInlineError($element, this.getRequiredMessage($element));
                return;
            }

            var msg = $element.attr('data-mask-error') || this.getMessage('invalidGeneric', 'Invalid value for this field');
            this.ensureErrorStyles();
            this.renderInlineError($element, msg);
        },

        /**
         * Handle live updates for non-masked fields opted into inline errors.
         *
         * @param {jQuery} $element Target element.
         */
        handleGenericLive: function($element) {
            if (!this.shouldShowInlineErrors($element)) {
                return;
            }

            var el = $element.get(0);

            if (this.isEmptyRequired($element)) {
                this.ensureErrorStyles();
                this.renderInlineError($element, this.getRequiredMessage($element));
                return;
            }

            var lengthMessage = this.getLengthMessage($element);

            if (lengthMessage) {
                this.ensureErrorStyles();
                this.renderInlineError($element, lengthMessage);
                return;
            }

            if (el && el.validity && el.validity.valid) {
                this.clearInlineError($element);
                return;
            }

            if (!$element.val()) {
                this.clearInlineError($element);
                return;
            }

            var msg = $element.attr('data-mask-error') || this.getMessage('invalidGeneric', 'Invalid value for this field');
            this.ensureErrorStyles();
            this.renderInlineError($element, msg);
        },

        /**
         * Build length validation message based on min/max attributes and validity.
         *
         * @param {jQuery} $element Target element.
         * @return {string} Message or empty string.
         */
        getLengthMessage: function($element) {
            var el = $element.get(0);

            if (!el || !el.validity) {
                return '';
            }

            if (el.validity.tooShort) {
                var min = $element.attr('minlength') || $element.attr('minLength') || $element.prop('minLength');
                return this.formatMessage(this.getMessage('tooShort', 'Please enter at least %s characters'), [min]);
            }

            if (el.validity.tooLong) {
                var max = $element.attr('maxlength') || $element.attr('maxLength') || $element.prop('maxLength');
                return this.formatMessage(this.getMessage('tooLong', 'Please enter no more than %s characters'), [max]);
            }

            return '';
        },

        /**
         * Inject and render the inline error element.
         *
         * @param {jQuery} $element Target element.
         * @param {string} message Error text.
         */
        renderInlineError: function($element, message) {
            var errorClass = $element.attr('data-mask-error-class') || this.settings.inlineErrorClass || 'mfhf-mask-error';
            var $error = $element.data('mfhfErrorEl');

            if (!$error || !$error.length) {
                $error = $('<span/>', {
                    'class': errorClass,
                    'aria-live': 'polite'
                });

                $element.data('mfhfErrorEl', $error);
                $element.after($error);
            } else {
                $error.attr('class', errorClass);
            }

            $error.text(message).show();
            this.addInvalidClass($element);
        },

        /**
         * Clear inline error for an element.
         *
         * @param {jQuery} $element Target element.
         */
        clearInlineError: function($element) {
            var $error = $element.data('mfhfErrorEl');

            if ($error && $error.length) {
                $error.remove();
                $element.removeData('mfhfErrorEl');
            }

            this.removeInvalidClass($element);
        },

        /**
         * Clear inline error when input becomes empty.
         *
         * @param {jQuery} $element Target element.
         */
        maybeClearInlineError: function($element) {
            if (!this.shouldShowInlineErrors($element)) {
                return;
            }

            if (!$element.val()) {
                this.clearInlineError($element);
            }
        },

        /**
         * Add invalid class to the element for styling.
         *
         * @param {jQuery} $element Target element.
         */
        addInvalidClass: function($element) {
            var cls = $element.attr('data-mask-error-input-class') || this.settings.invalidInputClass || 'mfhf-mask-invalid';

            if (cls) {
                $element.addClass(cls);
            }
        },

        /**
         * Remove invalid class from the element.
         *
         * @param {jQuery} $element Target element.
         */
        removeInvalidClass: function($element) {
            var cls = $element.attr('data-mask-error-input-class') || this.settings.invalidInputClass || 'mfhf-mask-invalid';

            if (cls) {
                $element.removeClass(cls);
            }
        },

        /**
         * Ensure minimal inline error styles exist.
         */
        ensureErrorStyles: function() {
            if (this.errorStylesInjected) {
                return;
            }

            var style = document.createElement('style');
            style.type = 'text/css';
            style.id = 'mfhf-inline-error-styles';
            style.appendChild(document.createTextNode('.mfhf-mask-error{display:block;color:#cc0000;font-size:12px;margin-top:4px;}'));

            var head = document.head || document.getElementsByTagName('head')[0];

            if (head) {
                head.appendChild(style);
                this.errorStylesInjected = true;
            }
        },

        /**
         * Determine if current value is incomplete against mask pattern.
         *
         * @param {jQuery} $element Target element.
         * @return {boolean} True when value is partially filled.
         */
        isIncomplete: function($element) {
            var info = this.getCompletenessInfo($element);

            if (!info.required) {
                return false;
            }

            return info.current > 0 && info.current < info.required;
        },

        /**
         * Get required and current lengths for completeness checks.
         *
         * @param {jQuery} $element Target element.
         * @return {{required: number, current: number}} Length info.
         */
        getCompletenessInfo: function($element) {
            var maskPattern = this.getMaskPattern($element);
            var required = this.countRequiredChars(maskPattern);
            var current = this.getCleanValue($element).length;

            return { required: required, current: current };
        },

        /**
         * Retrieve mask pattern from data attributes.
         *
         * @param {jQuery} $element Target element.
         * @return {string} Mask pattern or empty string.
         */
        getMaskPattern: function($element) {
            var direct = $element.attr('data-mask');

            if (direct) {
                return direct;
            }

            var stored = $element.data('mfhfMaskPattern');

            if (stored) {
                return stored;
            }

            return '';
        },

        /**
         * Count required characters in mask pattern (ignores optional 9).
         *
         * @param {string} maskPattern Mask string.
         * @return {number} Required character count.
         */
        countRequiredChars: function(maskPattern) {
            if (!maskPattern) {
                return 0;
            }

            var chars = maskPattern.split('');
            var required = 0;

            chars.forEach(function(ch) {
                if (ch === '9') {
                    return; // optional
                }

                if (ch === '0' || ch === 'A' || ch === 'S' || ch === '#') {
                    required += 1;
                }
            });

            return required;
        },

        /**
         * Check if element is required and empty.
         *
         * @param {jQuery} $element Target element.
         * @return {boolean} True when required and empty.
         */
        isEmptyRequired: function($element) {
            var isRequired = $element.is('[required]');
            return isRequired && !$element.val();
        }
    };

    // Initialize on DOM ready.
    $(document).ready(function() {
        MaskForHtmlForms.init();
    });

    // Expose to global scope for external access.
    window.MaskForHtmlForms = MaskForHtmlForms;

})(jQuery);

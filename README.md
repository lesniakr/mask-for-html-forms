# Mask for HTML Forms

A WordPress plugin that extends [HTML Forms](https://htmlformsplugin.com/) with input masking capabilities using [jQuery Mask Plugin](https://igorescobar.github.io/jQuery-Mask-Plugin/).

Add phone numbers, postal codes, dates, national IDs (PESEL), and custom masks to your form fields with simple HTML attributes.

## Features

- **Easy Mask Application** - Use `data-mask` attributes directly in HTML Forms editor
- **Built-in Presets** - Ready-to-use masks for common Polish formats (phone, postal code, PESEL, NIP, REGON)
- **Custom Masks** - Create any mask pattern using jQuery Mask Plugin syntax
- **Performance Optimized** - Scripts load only on pages with forms
- **Seamless Integration** - Works with HTML Forms without configuration
- **Extensible** - Hooks and filters for developers

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- [HTML Forms](https://wordpress.org/plugins/html-forms/) plugin (free version works)

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin
5. Ensure HTML Forms plugin is also active

## Usage

### Quick Start

Add the `data-mask` attribute to any input field in your HTML Forms markup:

```html
<input type="text" name="phone" data-mask="000 000 000" placeholder="000 000 000" />
```

Or use a preset:

```html
<input type="text" name="phone" data-mask-preset="phone-pl" placeholder="000 000 000" />
```

### Available Presets

| Preset Name | Description | Pattern | Example |
|-------------|-------------|---------|---------|
| `phone-pl` | Polish phone number | `000 000 000` | 123 456 789 |
| `phone-pl-intl` | Polish phone (international) | `+00 000 000 000` | +48 123 456 789 |
| `postal-pl` | Polish postal code | `00-000` | 00-001 |
| `pesel` | Polish national ID | `00000000000` | 12345678901 |
| `nip` | Polish tax ID | `000-000-00-00` | 123-456-78-90 |
| `regon` | Polish business registry | `000000000` | 123456789 |
| `date-eu` | Date (DD/MM/YYYY) | `00/00/0000` | 25/12/2024 |
| `date-iso` | Date (ISO format) | `0000-00-00` | 2024-12-25 |
| `time-24` | Time (24h) | `00:00` | 14:30 |
| `credit-card` | Credit card number | `0000 0000 0000 0000` | 1234 5678 9012 3456 |
| `iban-pl` | Polish IBAN | `AA 00 0000 0000 0000 0000 0000 0000` | PL 12 3456... |

### Mask Pattern Characters

| Character | Description |
|-----------|-------------|
| `0` | Required digit (0-9) |
| `9` | Optional digit (0-9) |
| `#` | Recursive digit (for variable-length numbers) |
| `A` | Required alphanumeric (A-Z, a-z, 0-9) |
| `S` | Required letter (A-Z, a-z) |

Any other character is used as a literal separator.

### Mask Options

Additional attributes for customizing mask behavior:

```html
<!-- Apply mask from right to left (for currency) -->
<input type="text" data-mask="#.##0,00" data-mask-reverse="true" />

<!-- Clear field if incomplete -->
<input type="text" data-mask="00/00/0000" data-mask-clearifnotmatch="true" />

<!-- Select all on focus -->
<input type="text" data-mask="00-000" data-mask-selectonfocus="true" />
```

### Complete Form Example

```html
<p>
    <label for="phone">Phone Number</label>
    <input type="text" name="phone" id="phone"
           data-mask-preset="phone-pl"
           placeholder="000 000 000" required />
</p>

<p>
    <label for="postal">Postal Code</label>
    <input type="text" name="postal" id="postal"
           data-mask="00-000"
           placeholder="00-000" required />
</p>

<p>
    <label for="pesel">PESEL</label>
    <input type="text" name="pesel" id="pesel"
           data-mask-preset="pesel"
           data-mask-clearifnotmatch="true" />
</p>

<p>
    <label for="birthdate">Birth Date</label>
    <input type="text" name="birthdate" id="birthdate"
           data-mask="00/00/0000"
           placeholder="DD/MM/YYYY" />
</p>

<p>
    <input type="submit" value="Submit" />
</p>
```

## Architecture

```
mask-for-html-forms/
├── mask-for-html-forms.php      # Main plugin file, bootstrap
├── includes/
│   ├── class-plugin.php         # Main orchestrator (singleton)
│   ├── class-assets-loader.php  # JS/CSS conditional loading
│   ├── class-form-detector.php  # Detects forms on page
│   └── class-admin-page.php     # Admin documentation page
├── assets/
│   ├── js/
│   │   ├── jquery.mask.min.js   # jQuery Mask Plugin v1.14.16
│   │   └── mask-init.js         # Initialization script
│   └── css/
│       └── admin.css            # Admin page styles
└── languages/                    # Translation files
```

### Key Design Decisions

1. **HTML Attributes as Configuration** - HTML Forms gives users full control over form markup, so `data-mask` attributes are the natural choice for mask configuration.

2. **Lazy Script Loading** - JavaScript loads only when forms are detected on the page, preventing unnecessary performance impact.

3. **No Database Storage** - Masks are defined in form HTML, eliminating the need for custom database tables or options.

4. **Documentation Page Instead of Settings** - A help page with examples provides more value than empty settings screens.

## Hooks & Filters

### PHP Filters

```php
// Force load scripts on specific pages
add_filter('mfhf_force_load_scripts', function($force) {
    if (is_page('contact')) {
        return true;
    }
    return $force;
});

// Add custom presets
add_filter('mfhf_mask_presets', function($presets) {
    $presets['phone-us'] = [
        'mask'    => '(000) 000-0000',
        'options' => [],
    ];
    return $presets;
});

// Add custom translations
add_filter('mfhf_mask_translations', function($translations) {
    $translations['X'] = [
        'pattern'  => '[0-9xX]',
        'optional' => false,
    ];
    return $translations;
});

// Modify script settings
add_filter('mfhf_script_settings', function($settings) {
    $settings['watchDynamicInputs'] = false;
    return $settings;
});
```

### JavaScript API

```javascript
// Get unmasked value
var cleanPhone = MaskForHtmlForms.getCleanValue('#phone');

// Apply mask programmatically
MaskForHtmlForms.applyMask('#custom-field', '000-000-000');

// Remove mask
MaskForHtmlForms.removeMask('#custom-field');
```

## FAQ

### Why aren't masks working?

1. Ensure HTML Forms plugin is active
2. Check browser console for JavaScript errors
3. Verify the `data-mask` or `data-mask-preset` attribute is present on input elements

### Can I use masks on non-input elements?

Yes, jQuery Mask Plugin supports `td`, `span`, and `div` elements. However, this is primarily designed for form inputs.

### Do masks validate input?

Masks format input but don't validate it. Use HTML Forms validation features or custom JavaScript for validation.

### Can I submit the unmasked value?

The masked value is submitted by default. To get unmasked values server-side, strip non-digit characters with PHP:

```php
$clean_phone = preg_replace('/\D/', '', $_POST['phone']);
```

## Roadmap

- [ ] Visual mask builder in admin
- [ ] Validation integration
- [ ] More international presets
- [ ] Phone number formatting by country
- [ ] Gutenberg block for preset reference

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

GPL v2 or later. See [LICENSE](LICENSE) for details.

## Credits

- [jQuery Mask Plugin](https://igorescobar.github.io/jQuery-Mask-Plugin/) by Igor Escobar
- [HTML Forms](https://htmlformsplugin.com/) by Link Software LLC

---

**Made for the WordPress community**

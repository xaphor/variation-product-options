# Variation Product Options

**Version:** 1.0.0 (MVP)  
**License:** GPL v3 or later  
**Author:** Zaffarullah  
**Contact:** xaphor.emam@gmail.com

## Description

Variation Product Options is a lightweight, secure, and modern WooCommerce plugin that allows store owners to add custom options (fields) to products at both the Product and Variation levels. Unlike existing free solutions, this plugin natively supports "Choice" buttons (Radio/Switch), conditional logic (chained fields), and dynamic pricing without requiring a premium upgrade.

### Core Philosophy

- **Zero Bloat:** No external API calls, minimal CSS/JS loading
- **Variation Aware:** Options can be specific to a variation (e.g., a "Gold" ring has different extra options than a "Silver" ring)
- **Transparency:** All extra costs are clearly broken down in the Cart and Order details

## Features

### Field Types Supported

- **Radio Buttons:** Standard circular selection (Single choice)
- **Radio Switch/Toggle:** A modern "pill" style switch (Yes/No or Option A/Option B)
- **Checkboxes:** Multiple selections (e.g., "Add Gift Wrap", "Add Card")
- **Dropdown Select:** For long lists of options
- **Datepicker:** A calendar input for delivery scheduling (Metadata only, no price impact)

### Pricing Logic

- **Flat Fee Addition:** Each option can have a defined price (e.g., +$50.00)
- **Dynamic Price Calculation:** Product price updates dynamically when options are selected (AJAX/JS-based)
- **Zero-Price Fields:** Fields like Datepicker or simple "Notes" can have a price of 0

### Conditional Logic (Chained Fields)

The system supports "If This, Then That" display rules:

- **Trigger:** Selection of a specific value in Field A
- **Action:** Reveal Field B

**Example:**
- Field A: "Want Installation?" (Yes/No)
- Condition: If "Yes" is selected → Show Field B
- Field B: "Is a Crane Required?" (Yes/No +$200)

### Scope & Assignment (Variation Logic)

Admin can assign a Field Group to:

- **All Products:** Global application
- **Specific Products:** Selected by Product ID
- **Specific Variations:** The critical feature

**Logic Examples:**
- If Product is "Palm Tree" AND Variation is "Large (10ft)", SHOW "Crane Option"
- If Product is "Palm Tree" AND Variation is "Small (2ft)", HIDE "Crane Option"

## Installation

### Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher

### Manual Installation

1. Download the plugin files
2. Upload the `variation-product-options` folder to `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to WooCommerce > Product Options to configure

## Usage

### Admin Interface

1. Go to **WooCommerce > Product Options** to create field groups
2. Or edit a product and use the **Product Options** meta box
3. Configure fields with:
   - Label (e.g., "Installation")
   - Type (Radio, Checkbox, etc.)
   - Options (e.g., Yes | No)
   - Price per Option
   - Condition (Show if Field X = Y)
   - Assignment (All Products, Specific Products, or Specific Variations)

### Frontend

- Fields display immediately before the "Add to Cart" button
- Modern and sleek styling with mobile-responsive design
- Radio switches appear as mobile toggles or segmented controls
- Dynamic price updates as options are selected

### Cart & Orders

- Selected options are displayed in the cart with price breakdown
- Options persist through checkout
- Order details show all selected options in the backend
- Options are included in order emails

## Data Structure

Field groups are stored as JSON in post meta with the following structure:

```json
{
  "group_id": "installation_options",
  "rules": {
    "variation_ids": [102, 105],
    "product_ids": []
  },
  "fields": [
    {
      "id": "install_check",
      "type": "radio_switch",
      "label": "Want Installation?",
      "options": [
        {"label": "Yes", "price": 50, "value": "yes"},
        {"label": "No", "price": 0, "value": "no"}
      ]
    },
    {
      "id": "crane_check",
      "type": "checkbox",
      "label": "Crane Required?",
      "price": 200,
      "condition": {"field": "install_check", "value": "yes"}
    }
  ]
}
```

## Security & Performance

- All user inputs are sanitized using `sanitize_text_field()`
- Required fields are validated before allowing "Add to Cart"
- SQL queries use `$wpdb->prepare()` for safety
- Admin forms are secured with WP Nonces
- Minimal CSS/JS loading for optimal performance

## Development

### File Structure

```
variation-product-options/
├── variation-product-options.php  # Main plugin file
├── includes/
│   ├── class-vpo-core.php         # Core functionality
│   ├── class-vpo-data-handler.php  # Data management
│   └── class-vpo-field-types.php  # Field type handlers
├── admin/
│   ├── class-vpo-admin.php        # Admin interface
│   └── class-vpo-field-builder.php # Field builder
├── frontend/
│   ├── class-vpo-frontend.php     # Frontend display
│   └── class-vpo-cart-handler.php # Cart integration
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
├── languages/                      # Translation files
├── LICENSE                         # GPL v3 License
└── README.md                       # This file
```

### Hooks & Filters

The plugin uses standard WooCommerce hooks:

- `woocommerce_before_add_to_cart_button` - Display fields
- `woocommerce_add_cart_item_data` - Add option data to cart
- `woocommerce_before_calculate_totals` - Modify cart item price
- `woocommerce_checkout_create_order_line_item` - Save to order meta

## Support

For issues, feature requests, or contributions, please contact:
- **Email:** xaphor.emam@gmail.com

## Changelog

### 1.0.0 (MVP)
- Initial release
- Basic field types support
- Variation-level assignment
- Conditional logic framework
- Dynamic pricing framework
- Cart and order integration

## License

This plugin is licensed under the GPL v3 or later.

```
Copyright (C) 2024 Zaffarullah

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

Developed by Zaffarullah for the WooCommerce community.
